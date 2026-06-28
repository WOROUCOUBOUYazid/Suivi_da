<?php

namespace App\Services;

use App\Models\DemandeAchat;
use App\Models\HistoriqueStatut;
use App\Models\Statut;
use App\Models\User;
use App\Notifications\ChangementStatutNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Gère les transitions de statut d'une Demande d'Achat et les règles métier
 * associées (avance/retour, clôture, historisation, réinitialisation des relances).
 *
 * Les statuts sont ordonnés par leur champ `ordre`, ce qui permet d'ajouter de
 * nouveaux statuts sans modifier ce code.
 */
class WorkflowService
{
    public function __construct(
        private RelanceService $relances,
        private LogService $logs,
        private NotificationService $notifications,
    ) {}

    /**
     * Indique si l'utilisateur peut faire passer la DA vers le statut cible.
     *
     * - Une clôture est toujours autorisée (intégration d'anciennes DA).
     * - Un administrateur (permission « close da » + « view all da ») peut
     *   revenir à un statut antérieur.
     * - Un utilisateur standard ne peut qu'avancer (ordre strictement supérieur).
     */
    public function peutTransiter(User $user, DemandeAchat $da, Statut $cible): bool
    {
        $actuel = $da->statut;

        if ($actuel && $actuel->id === $cible->id) {
            return false;
        }

        if ($cible->est_cloture) {
            return true;
        }

        if ($this->estAdministrateur($user)) {
            return true;
        }

        // Utilisateur standard : avance uniquement.
        return ! $actuel || $cible->ordre > $actuel->ordre;
    }

    /**
     * Applique un changement de statut : valide la transition, historise,
     * met à jour la DA, gère la clôture et réinitialise les relances.
     */
    public function changerStatut(
        DemandeAchat $da,
        Statut $cible,
        User $user,
        ?string $commentaire = null,
        ?string $dateEstimeeAction = null,
        ?int $delaiPersonnalise = null,
    ): DemandeAchat {
        if (! $this->peutTransiter($user, $da, $cible)) {
            throw ValidationException::withMessages([
                'statut_id' => ['Transition de statut non autorisée pour cet utilisateur.'],
            ]);
        }

        $ancienStatut = $da->statut;

        return DB::transaction(function () use ($da, $cible, $user, $commentaire, $dateEstimeeAction, $delaiPersonnalise, $ancienStatut) {
            $da->statut_id = $cible->id;
            $da->updated_by = $user->id;

            if (! is_null($dateEstimeeAction)) {
                $da->date_estimee_action = $dateEstimeeAction;
            }
            if (! is_null($delaiPersonnalise)) {
                $da->delai_personnalise_relance_jours = $delaiPersonnalise;
            }

            if ($cible->est_cloture && ! $da->date_cloture) {
                $da->date_cloture = now();
            }

            $da->save();

            HistoriqueStatut::create([
                'demande_achat_id' => $da->id,
                'ancien_statut_id' => $ancienStatut?->id,
                'nouveau_statut_id' => $cible->id,
                'commentaire' => $commentaire,
                'utilisateur_id' => $user->id,
                'date_changement' => now(),
            ]);

            $da->refresh();
            $this->relances->reinitialiserPour($da);

            $this->logs->enregistrer(
                $cible->est_cloture ? LogService::DA_CLOTURE : LogService::DA_CHANGEMENT_STATUT,
                "DA {$da->numero_da} : {$ancienStatut?->libelle} → {$cible->libelle}",
                donneesAvant: ['statut_id' => $ancienStatut?->id],
                donneesApres: ['statut_id' => $cible->id],
                utilisateur: $user,
            );

            // Notifie le demandeur (créateur) du changement de statut.
            if ($createur = $da->createur) {
                $this->notifications->envoyer(
                    $createur,
                    new ChangementStatutNotification($da, $ancienStatut, $cible),
                    NotificationService::TYPE_CHANGEMENT_STATUT,
                );
            }

            return $da;
        });
    }

    private function estAdministrateur(User $user): bool
    {
        return $user->can('view all da');
    }
}

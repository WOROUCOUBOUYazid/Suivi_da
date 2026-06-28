<?php

namespace App\Services;

use App\Models\DemandeAchat;
use App\Models\HistoriqueStatut;
use App\Models\Statut;
use App\Models\User;
use App\Notifications\DaCreeeNotification;
use Illuminate\Support\Facades\DB;

/**
 * Logique métier de création / modification / clôture des Demandes d'Achat.
 */
class DemandeAchatService
{
    public function __construct(
        private RelanceService $relances,
        private WorkflowService $workflow,
        private LogService $logs,
        private PdfService $pdf,
        private NotificationService $notifications,
    ) {}

    /**
     * Crée une nouvelle DA, historise le statut initial et planifie la 1re relance.
     *
     * @param  array<string,mixed>  $donnees
     */
    public function creer(array $donnees, User $user): DemandeAchat
    {
        return DB::transaction(function () use ($donnees, $user) {
            $statut = isset($donnees['statut_id'])
                ? Statut::findOrFail($donnees['statut_id'])
                : Statut::orderBy('ordre')->firstOrFail();

            $donnees['statut_id'] = $statut->id;
            $donnees['created_by'] = $user->id;
            $donnees['date_creation_application'] = now()->toDateString();

            if ($statut->est_cloture && empty($donnees['date_cloture'])) {
                $donnees['date_cloture'] = now()->toDateString();
            }

            $da = DemandeAchat::create($donnees);

            HistoriqueStatut::create([
                'demande_achat_id' => $da->id,
                'ancien_statut_id' => null,
                'nouveau_statut_id' => $statut->id,
                'commentaire' => 'Création de la demande',
                'utilisateur_id' => $user->id,
                'date_changement' => now(),
            ]);

            $this->relances->reinitialiserPour($da);

            // Génération automatique de la fiche PDF.
            $this->pdf->generer($da);

            $this->logs->enregistrer(
                LogService::DA_CREATION,
                "Création de la DA {$da->numero_da}",
                donneesApres: $da->only(array_keys($donnees)),
                utilisateur: $user,
            );

            $this->notifications->envoyer(
                $user,
                new DaCreeeNotification($da->load('statut')),
                NotificationService::TYPE_DA_CREEE,
            );

            return $da->fresh(['statut', 'createur']);
        });
    }

    /**
     * Met à jour les champs d'une DA (hors changement de statut).
     *
     * @param  array<string,mixed>  $donnees
     */
    public function mettreAJour(DemandeAchat $da, array $donnees, User $user): DemandeAchat
    {
        $avant = $da->toArray();

        $donnees['updated_by'] = $user->id;
        $da->update($donnees);

        $this->logs->enregistrer(
            LogService::DA_MODIFICATION,
            "Modification de la DA {$da->numero_da}",
            donneesAvant: $avant,
            donneesApres: $da->fresh()->toArray(),
            utilisateur: $user,
        );

        return $da->fresh(['statut', 'createur']);
    }

    /**
     * Clôture une DA (transition vers le statut de clôture).
     */
    public function cloturer(DemandeAchat $da, User $user, ?string $commentaire = null): DemandeAchat
    {
        $statutCloture = Statut::where('est_cloture', true)->orderBy('ordre')->firstOrFail();

        return $this->workflow->changerStatut($da, $statutCloture, $user, $commentaire);
    }

    public function supprimer(DemandeAchat $da, User $user): void
    {
        $this->logs->enregistrer(
            LogService::DA_SUPPRESSION,
            "Suppression de la DA {$da->numero_da}",
            donneesAvant: $da->toArray(),
            utilisateur: $user,
        );

        $da->delete();
    }
}

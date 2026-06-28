<?php

namespace App\Services;

use App\Models\ConfigurationRelance;
use App\Models\DemandeAchat;
use App\Models\Relance;
use App\Notifications\RelanceNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Moteur de relances configurable.
 *
 * Détermine, pour chaque statut, la prochaine date de relance à partir de la
 * configuration dédiée (table configuration_relances) ou d'un délai / date
 * personnalisé saisi sur la DA. L'envoi effectif est assuré par
 * App\Jobs\EnvoyerRelancesJob via le scheduler.
 */
class RelanceService
{
    public function __construct(private NotificationService $notifications) {}

    /**
     * Réinitialise le compteur de relance d'une DA après un changement de statut.
     *
     * Les relances non encore envoyées sont supprimées, puis une nouvelle
     * première relance est planifiée si le statut courant l'autorise.
     */
    public function reinitialiserPour(DemandeAchat $da): void
    {
        $da->relances()->where('envoyee', false)->delete();

        $this->planifierProchaine($da, numeroRelance: 1);
    }

    /**
     * Planifie la prochaine relance d'une DA (création d'une ligne `relances`).
     *
     * Retourne null si aucune relance n'est applicable (DA clôturée, statut
     * sans configuration active).
     */
    public function planifierProchaine(DemandeAchat $da, int $numeroRelance = 1): ?Relance
    {
        $statut = $da->statut;

        if (! $statut || $statut->est_cloture || $da->date_cloture) {
            return null;
        }

        $config = ConfigurationRelance::where('statut_id', $statut->id)->first();

        if (! $config || ! $config->actif) {
            return null;
        }

        $datePrevue = $this->calculerDatePrevue($da, $config, $numeroRelance);

        return Relance::create([
            'demande_achat_id' => $da->id,
            'statut_id' => $statut->id,
            'date_relance_prevue' => $datePrevue,
            'envoyee' => false,
            'numero_relance' => $numeroRelance,
        ]);
    }

    /**
     * Relances arrivées à échéance et non encore envoyées.
     *
     * @return Collection<int, Relance>
     */
    public function relancesDues(?Carbon $date = null): Collection
    {
        $date ??= now();

        return Relance::with(['demandeAchat.statut', 'demandeAchat.createur'])
            ->where('envoyee', false)
            ->whereDate('date_relance_prevue', '<=', $date->toDateString())
            ->get()
            // Exclure les DA clôturées entre-temps.
            ->filter(fn (Relance $r) => $r->demandeAchat && ! $r->demandeAchat->date_cloture);
    }

    /**
     * Envoie une relance : notifie le demandeur, marque la relance envoyée
     * et planifie automatiquement la relance suivante.
     */
    public function envoyer(Relance $relance): void
    {
        $da = $relance->demandeAchat;

        if (! $da || $da->date_cloture) {
            return;
        }

        if ($createur = $da->createur) {
            $this->notifications->envoyer(
                $createur,
                new RelanceNotification($da->loadMissing('statut'), $relance),
                NotificationService::TYPE_RELANCE,
            );
        }

        $relance->update([
            'envoyee' => true,
            'date_relance_envoyee' => now()->toDateString(),
        ]);

        // Planifie la relance suivante (délai « relances suivantes »).
        $this->planifierProchaine($da, $relance->numero_relance + 1);
    }

    /**
     * Traite toutes les relances dues. Retourne le nombre de relances envoyées.
     */
    public function traiterRelancesDues(): int
    {
        $dues = $this->relancesDues();

        foreach ($dues as $relance) {
            $this->envoyer($relance);
        }

        return $dues->count();
    }

    /**
     * Calcule la date prévue d'une relance.
     *
     * Priorité : date estimée d'action > délai personnalisé saisi sur la DA >
     * délais standards de la configuration du statut.
     */
    private function calculerDatePrevue(DemandeAchat $da, ConfigurationRelance $config, int $numeroRelance): Carbon
    {
        // Une date estimée d'action explicite prime (uniquement pour la 1re relance).
        if ($numeroRelance === 1 && $da->date_estimee_action) {
            return Carbon::parse($da->date_estimee_action)->startOfDay();
        }

        if ($numeroRelance === 1 && $da->delai_personnalise_relance_jours) {
            return now()->startOfDay()->addDays($da->delai_personnalise_relance_jours);
        }

        $delai = $numeroRelance === 1
            ? $config->delai_premiere_relance_jours
            : $config->delai_relance_suivante_jours;

        return now()->startOfDay()->addDays($delai);
    }
}

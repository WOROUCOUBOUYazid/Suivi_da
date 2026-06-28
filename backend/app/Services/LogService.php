<?php

namespace App\Services;

use App\Models\Log;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Service centralisé de journalisation applicative.
 *
 * Trace les évènements métier (connexions, DA, statuts, commentaires,
 * notifications, relances…) dans la table `logs` avec, le cas échéant,
 * l'état des données avant / après modification.
 */
class LogService
{
    // Actions journalisées
    public const CONNEXION = 'connexion';

    public const DECONNEXION = 'deconnexion';

    public const DA_CREATION = 'da_creation';

    public const DA_MODIFICATION = 'da_modification';

    public const DA_SUPPRESSION = 'da_suppression';

    public const DA_CHANGEMENT_STATUT = 'da_changement_statut';

    public const DA_CLOTURE = 'da_cloture';

    public const COMMENTAIRE_AJOUT = 'commentaire_ajout';

    public const NOTIFICATION_ENVOI = 'notification_envoi';

    public const RELANCE_ENVOI = 'relance_envoi';

    public const PARAMETRE_MODIFICATION = 'parametre_modification';

    public const UTILISATEUR_GESTION = 'utilisateur_gestion';

    /**
     * Enregistre une entrée de log.
     *
     * @param  array<string,mixed>|null  $donneesAvant
     * @param  array<string,mixed>|null  $donneesApres
     */
    public function enregistrer(
        string $action,
        ?string $description = null,
        ?array $donneesAvant = null,
        ?array $donneesApres = null,
        ?User $utilisateur = null,
    ): Log {
        return Log::create([
            'utilisateur_id' => $utilisateur?->id ?? Auth::id(),
            'action' => $action,
            'description' => $description,
            'donnees_avant' => $donneesAvant,
            'donnees_apres' => $donneesApres,
            'ip_address' => Request::ip(),
        ]);
    }
}

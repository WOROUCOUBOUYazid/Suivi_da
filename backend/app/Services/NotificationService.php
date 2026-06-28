<?php

namespace App\Services;

use App\Models\NotificationHistorique;
use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Envoi et historisation des notifications applicatives.
 *
 * Respecte le paramètre `notifications_email_actif` et trace chaque
 * notification dans la table notifications_historique.
 */
class NotificationService
{
    public const TYPE_DA_CREEE = 'da_creee';

    public const TYPE_CHANGEMENT_STATUT = 'changement_statut';

    public const TYPE_RELANCE = 'relance';

    public function __construct(
        private ParametreService $params,
        private LogService $logs,
    ) {}

    /**
     * Envoie une notification à un destinataire et l'historise.
     *
     * La notification doit exposer des propriétés publiques `sujet` et `corps`
     * pour l'historisation.
     */
    public function envoyer(User $destinataire, Notification $notification, string $type): NotificationHistorique
    {
        $actif = $this->params->getBool('notifications_email_actif', true);

        $historique = NotificationHistorique::create([
            'destinataire_id' => $destinataire->id,
            'type' => $type,
            'sujet' => $notification->sujet ?? $type,
            'contenu' => $notification->corps ?? '',
            'canal' => 'email',
            'envoyee' => false,
        ]);

        if ($actif) {
            $destinataire->notify($notification);

            $historique->update(['envoyee' => true, 'date_envoi' => now()]);

            $this->logs->enregistrer(
                LogService::NOTIFICATION_ENVOI,
                "Notification « {$type} » envoyée à {$destinataire->email}",
                utilisateur: $destinataire,
            );
        }

        return $historique;
    }
}

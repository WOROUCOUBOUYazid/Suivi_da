<?php

namespace App\Notifications;

use App\Models\DemandeAchat;
use App\Models\Relance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RelanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $sujet;

    public string $corps;

    public function __construct(
        public DemandeAchat $da,
        public Relance $relance,
    ) {
        $this->sujet = "Relance — DA {$da->numero_da}";
        $this->corps = "La DA {$da->numero_da} est au statut « {$da->statut?->libelle} » depuis un certain temps. "
            ."Pensez à faire le point auprès du service achat (relance n°{$relance->numero_relance}).";
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->sujet)
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line($this->corps);
    }
}

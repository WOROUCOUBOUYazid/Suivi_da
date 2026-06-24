<?php

namespace App\Notifications;

use App\Models\DemandeAchat;
use App\Models\Statut;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangementStatutNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $sujet;

    public string $corps;

    public function __construct(
        public DemandeAchat $da,
        public ?Statut $ancien,
        public Statut $nouveau,
    ) {
        $this->sujet = "DA {$da->numero_da} : changement de statut";
        $this->corps = "Le statut de la DA {$da->numero_da} est passé de « {$ancien?->libelle} » à « {$nouveau->libelle} ».";
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

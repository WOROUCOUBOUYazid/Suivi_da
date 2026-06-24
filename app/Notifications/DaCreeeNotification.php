<?php

namespace App\Notifications;

use App\Models\DemandeAchat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DaCreeeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $sujet;

    public string $corps;

    public function __construct(public DemandeAchat $da)
    {
        $this->sujet = "Nouvelle demande d'achat {$da->numero_da}";
        $this->corps = "La demande d'achat {$da->numero_da} (« {$da->designation} ») a été enregistrée.";
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
            ->line($this->corps)
            ->line("Statut initial : {$this->da->statut?->libelle}.")
            ->line('Vous recevrez des relances tant que la demande n\'aura pas avancé.');
    }
}

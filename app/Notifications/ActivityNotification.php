<?php

namespace App\Notifications;

use App\Enums\EmailCategory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Symfony\Component\Mime\Email;

/**
 * E-mail envoyé en complément d'une notification de la plateforme
 * (offre, commande, livraison, alerte, message).
 */
class ActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public EmailCategory $category,
        public string $title,
        public string $body,
        public ?string $actionUrl = null,
        public string $actionText = 'Voir sur Seconde Main 224',
    ) {}

    public function via(object $notifiable): array
    {
        // Revérifié à l'envoi : les préférences ont pu changer depuis la mise en file.
        return $notifiable->wantsEmailFor($this->category) ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $unsubscribeUrl = URL::signedRoute('email.unsubscribe', [
            'user' => $notifiable->id,
            'category' => $this->category->value,
        ]);

        return (new MailMessage)
            ->subject($this->title)
            ->greeting("Bonjour {$notifiable->name},")
            ->line($this->body)
            ->action($this->actionText, $this->actionUrl ?? $this->defaultActionUrl())
            ->salutation("L'équipe ".config('app.name'))
            ->line("Vous recevez cet e-mail car les notifications « {$this->category->label()} » sont activées. [Ne plus recevoir ces e-mails]({$unsubscribeUrl}) · [Gérer mes préférences](".route('profile.email-preferences').')')
            ->withSymfonyMessage(function (Email $message) use ($unsubscribeUrl) {
                $message->getHeaders()->addTextHeader('List-Unsubscribe', "<{$unsubscribeUrl}>");
                $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
    }

    private function defaultActionUrl(): string
    {
        return match ($this->category) {
            EmailCategory::Offres => route('profile.offers.index'),
            EmailCategory::Alertes => route('profile.alerts.index'),
            default => route('notifications.index'),
        };
    }
}

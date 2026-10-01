<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail de bienvenue envoyé à l'inscription. Il contient aussi le lien de
 * confirmation d'adresse quand celle-ci n'est pas encore vérifiée, pour
 * éviter d'envoyer deux e-mails coup sur coup.
 */
class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return $notifiable->email ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name');

        $message = (new MailMessage)
            ->subject("Bienvenue sur {$appName} !")
            ->greeting("Bienvenue {$notifiable->name} !")
            ->line("Votre compte {$appName} est créé. Vous pouvez dès maintenant acheter et vendre des articles d'occasion près de chez vous.");

        if (! $notifiable->hasVerifiedEmail()) {
            return $message
                ->line('Pour commencer, confirmez votre adresse e-mail :')
                ->action('Confirmer mon adresse e-mail', (new VerifyEmailNotification)->urlFor($notifiable))
                ->line("Si vous n'êtes pas à l'origine de cette inscription, ignorez simplement cet e-mail.");
        }

        return $message->action('Découvrir les annonces', url('/'));
    }
}

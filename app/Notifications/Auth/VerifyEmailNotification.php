<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Lien de confirmation d'adresse e-mail (renvoi, ou après changement d'e-mail).
 */
class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirmez votre adresse e-mail')
            ->greeting('Bonjour !')
            ->line('Cliquez sur le bouton ci-dessous pour confirmer votre adresse e-mail.')
            ->action('Confirmer mon adresse e-mail', $url)
            ->line('Ce lien est valable '.$this->expiryLabel().'.')
            ->line("Si vous n'avez pas créé de compte sur ".config('app.name').', ignorez simplement cet e-mail.');
    }

    /**
     * URL signée de confirmation, partagée avec l'e-mail de bienvenue.
     */
    public function urlFor($notifiable): string
    {
        return $this->verificationUrl($notifiable);
    }

    private function expiryLabel(): string
    {
        $minutes = (int) config('auth.verification.expire', 60);

        return $minutes % 60 === 0 ? ($minutes / 60).' heure(s)' : $minutes.' minutes';
    }
}

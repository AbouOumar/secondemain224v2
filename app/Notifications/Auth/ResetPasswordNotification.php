<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Lien de réinitialisation du mot de passe, en français.
 */
class ResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    protected function buildMailMessage($url): MailMessage
    {
        $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe')
            ->greeting('Bonjour !')
            ->line('Vous recevez cet e-mail car une demande de réinitialisation du mot de passe a été faite pour votre compte.')
            ->action('Choisir un nouveau mot de passe', $url)
            ->line("Ce lien expirera dans {$expire} minutes.")
            ->line("Si vous n'avez rien demandé, ignorez cet e-mail : votre mot de passe reste inchangé.");
    }
}

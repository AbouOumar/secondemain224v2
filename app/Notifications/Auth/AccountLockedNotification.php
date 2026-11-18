<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail de sécurité : la connexion au compte a été bloquée après trop d'échecs.
 * Toujours envoyé (pas soumis aux préférences d'e-mails), si le compte a une adresse.
 */
class AccountLockedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $seconds,
        public string $ip,
    ) {}

    public function via(object $notifiable): array
    {
        return $notifiable->email ? ['mail'] : [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = max(1, (int) ceil($this->seconds / 60));

        return (new MailMessage)
            ->subject('Tentatives de connexion inhabituelles sur votre compte')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Plusieurs tentatives de connexion avec un mot de passe incorrect ont eu lieu sur votre compte (adresse IP : {$this->ip}).")
            ->line("Par sécurité, la connexion est bloquée pendant {$minutes} minutes.")
            ->line("Si c'était vous, patientez ou réinitialisez votre mot de passe : cela lève le blocage.")
            ->action('Réinitialiser mon mot de passe', route('password.request'))
            ->line("Si ce n'était pas vous, réinitialisez votre mot de passe sans attendre et ne le communiquez à personne.")
            ->salutation("L'équipe ".config('app.name'));
    }
}

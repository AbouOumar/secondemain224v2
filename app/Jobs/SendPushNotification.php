<?php
namespace App\Jobs;
use App\Models\User;
use App\Services\Notification\FcmClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Envoie une notification push sur tous les téléphones de l'utilisateur
 * et oublie les jetons que Firebase signale comme invalides.
 */
class SendPushNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public string $title,
        public string $body,
        public array $data = []
    ) {}

    public function handle(FcmClient $fcm): void
    {
        if (! $fcm->isConfigured()) {
            return;
        }

        foreach ($this->user->deviceTokens as $device) {
            if ($fcm->send($device->token, $this->title, $this->body, $this->data) === FcmClient::INVALID_TOKEN) {
                $device->delete();
            }
        }
    }
}

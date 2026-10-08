<?php
namespace App\Jobs;
use App\Models\User;
use App\Services\Notification\FcmClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Envoie une notification push sur tous les téléphones de l'utilisateur
 * et oublie les jetons que Firebase signale comme invalides.
 */
class SendPushNotification implements ShouldQueue
{
    use Queueable;

    /** Utilisateur supprimé entre-temps : abandonner sans erreur. */
    public bool $deleteWhenMissingModels = true;

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

        // Un échec sur un téléphone ne doit ni bloquer les autres ni faire rejouer
        // la tâche (ce qui renverrait la notification aux téléphones déjà servis).
        foreach ($this->user->deviceTokens as $device) {
            try {
                if ($fcm->send($device->token, $this->title, $this->body, $this->data) === FcmClient::INVALID_TOKEN) {
                    Log::info('Téléphone désinscrit de Firebase, supprimé', ['user_id' => $this->user->id, 'device_id' => $device->id]);
                    $device->delete();
                }
            } catch (Throwable $e) {
                Log::warning('Échec envoi push', ['user_id' => $this->user->id, 'device_id' => $device->id, 'error' => $e->getMessage()]);
            }
        }
    }
}

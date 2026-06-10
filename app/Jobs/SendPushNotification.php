<?php
namespace App\Jobs;
use App\Services\Notification\FirebaseNotificationService;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendPushNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public string $title,
        public string $body,
        public array $data = []
    ) {}

    public function handle(FirebaseNotificationService $fcm): void
    {
        $fcm->send($this->user, $this->title, $this->body, 'push', $this->data);
    }
}

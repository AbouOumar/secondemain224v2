<?php
namespace App\Services\Notification;
use App\Models\User;
use App\Models\Notification;

class FirebaseNotificationService {
    public function send(User $user, string $title, string $message, string $type, array $data = []): void {
        // TODO: Intégration FCM
        Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public function sendToMultiple(array $users, string $title, string $message, string $type, array $data = []): void {
        foreach ($users as $user) {
            $this->send($user, $title, $message, $type, $data);
        }
    }
}

<?php
namespace App\Services\Notification;
use App\Enums\EmailCategory;
use App\Models\User;
use App\Models\Notification;
use App\Notifications\ActivityNotification;

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

        // Copie par e-mail si l'utilisateur l'accepte pour cette catégorie.
        $category = EmailCategory::forNotificationType($type);
        if ($category && $user->wantsEmailFor($category)) {
            $user->notify(new ActivityNotification($category, $title, $message));
        }
    }

    public function sendToMultiple(array $users, string $title, string $message, string $type, array $data = []): void {
        foreach ($users as $user) {
            $this->send($user, $title, $message, $type, $data);
        }
    }
}

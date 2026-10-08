<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Services\Chat\ChatService;
use App\Services\Notification\FirebaseNotificationService;
use App\Services\Notification\PushRoute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake(); // pas d'e-mails dans ces tests

        // Faux compte de service avec une vraie clé RSA (signature du jeton OAuth).
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        openssl_pkey_export($key, $pem);
        $path = storage_path('framework/testing/firebase-test.json');
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, json_encode([
            'client_email' => 'push@seconde-main-224.iam.gserviceaccount.com',
            'private_key' => $pem,
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ]));
        config(['firebase.project_id' => 'seconde-main-224', 'firebase.credentials' => $path]);
        cache()->flush();
    }

    private function fakeGoogle(int $fcmStatus = 200, array $fcmBody = ['name' => 'projects/x/messages/1']): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'oauth-token', 'expires_in' => 3600]),
            'fcm.googleapis.com/*' => Http::response($fcmBody, $fcmStatus),
        ]);
    }

    private function article(): Article
    {
        $category = Category::create(['libelle' => 'Motos', 'slug' => 'motos', 'icon' => 'bike']);

        return Article::create([
            'user_id' => User::factory()->create()->id, 'category_id' => $category->id,
            'titre' => 'Moto', 'slug' => 'moto-abc', 'description' => 'd', 'prix' => 1,
            'currency' => 'GNF', 'localisation' => 'K', 'etat' => 'bon', 'with_delivery' => false, 'is_published' => true,
        ]);
    }

    public function test_platform_notification_is_pushed_to_each_device_with_route(): void
    {
        $this->fakeGoogle();
        $user = User::factory()->create();
        $user->deviceTokens()->create(['token' => 'tok-1', 'platform' => 'android']);
        $article = $this->article();

        app(FirebaseNotificationService::class)->send($user, 'Nouvelle offre reçue', 'Awa propose 50 000 GNF', 'nouvelle_offre', ['article_id' => $article->id]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'fcm.googleapis.com/v1/projects/seconde-main-224/messages:send')
            && $request->hasHeader('Authorization', 'Bearer oauth-token')
            && $request['message']['token'] === 'tok-1'
            && $request['message']['notification']['title'] === 'Nouvelle offre reçue'
            && $request['message']['data'] === ['type' => 'nouvelle_offre', 'route' => '/article/?slug=moto-abc']);
    }

    public function test_unregistered_token_is_deleted(): void
    {
        $this->fakeGoogle(404, ['error' => ['status' => 'NOT_FOUND', 'details' => [['errorCode' => 'UNREGISTERED']]]]);
        $user = User::factory()->create();
        $user->deviceTokens()->create(['token' => 'tok-old', 'platform' => 'android']);

        app(FirebaseNotificationService::class)->send($user, 'T', 'B', 'livraison.livree', []);

        $this->assertDatabaseMissing('device_tokens', ['token' => 'tok-old']);
    }

    public function test_nothing_is_sent_without_device_or_configuration(): void
    {
        $this->fakeGoogle();
        $withoutDevice = User::factory()->create();
        app(FirebaseNotificationService::class)->send($withoutDevice, 'T', 'B', 'nouvelle_offre', []);

        config(['firebase.project_id' => null]);
        $withDevice = User::factory()->create();
        $withDevice->deviceTokens()->create(['token' => 'tok-1', 'platform' => 'android']);
        app(FirebaseNotificationService::class)->send($withDevice, 'T', 'B', 'nouvelle_offre', []);

        Http::assertNothingSent();
        $this->assertDatabaseCount('notifications', 2); // la notification sur le site reste créée
    }

    public function test_new_message_is_pushed_with_conversation_route(): void
    {
        $this->fakeGoogle();
        $article = $this->article(); // messages.article_id est une clé étrangère
        $sender = User::factory()->create(['name' => 'Awa']);
        $receiver = User::factory()->create();
        $receiver->deviceTokens()->create(['token' => 'tok-r', 'platform' => 'android']);

        app(ChatService::class)->sendMessage($sender, $receiver, 'Bonjour, toujours disponible ?', $article->id);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'messages:send')
            && $request['message']['notification']['title'] === 'Nouveau message de Awa'
            && $request['message']['data']['route'] === '/conversation/?user='.$sender->id.'&article_id='.$article->id.'&name=Awa');
    }

    public function test_route_falls_back_when_article_is_gone(): void
    {
        $this->assertSame('/profile/', PushRoute::forNotification('nouvelle_offre', ['article_id' => 999]));
        $this->assertSame('/profile/', PushRoute::forNotification('achat.valide', ['order_reference' => 'CMD-1']));
    }

    public function test_configuration_errors_never_delete_devices(): void
    {
        // Mauvais ID de projet : 404 sans « UNREGISTERED ».
        $this->fakeGoogle(404, ['error' => ['status' => 'NOT_FOUND', 'message' => 'Requested entity was not found.']]);
        $user = User::factory()->create();
        $user->deviceTokens()->create(['token' => 'tok-1', 'platform' => 'android']);

        app(FirebaseNotificationService::class)->send($user, 'T', 'B', 'livraison.livree', []);

        $this->assertDatabaseHas('device_tokens', ['token' => 'tok-1']);
    }

    public function test_one_failing_device_does_not_stop_the_others(): void
    {
        Http::fake(function ($request) {
            if (str_contains($request->url(), 'oauth2')) {
                return Http::response(['access_token' => 'oauth-token']);
            }
            if ($request['message']['token'] === 'tok-a') {
                throw new \Illuminate\Http\Client\ConnectionException('Délai dépassé');
            }

            return Http::response(['name' => 'ok']);
        });
        $user = User::factory()->create();
        $user->deviceTokens()->create(['token' => 'tok-a', 'platform' => 'android']);
        $user->deviceTokens()->create(['token' => 'tok-b', 'platform' => 'android']);

        app(FirebaseNotificationService::class)->send($user, 'T', 'B', 'livraison.livree', []);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'messages:send') && $request['message']['token'] === 'tok-b');
        $this->assertSame(2, $user->deviceTokens()->count());
    }

    public function test_expired_google_token_is_forgotten(): void
    {
        $this->fakeGoogle(401, ['error' => ['status' => 'UNAUTHENTICATED']]);
        $user = User::factory()->create();
        $user->deviceTokens()->create(['token' => 'tok-1', 'platform' => 'android']);

        app(FirebaseNotificationService::class)->send($user, 'T', 'B', 'livraison.livree', []);

        $this->assertFalse(cache()->has('fcm_access_token'));
        $this->assertDatabaseHas('device_tokens', ['token' => 'tok-1']);
    }

    public function test_long_notification_text_is_shortened(): void
    {
        $this->fakeGoogle();
        $user = User::factory()->create();
        $user->deviceTokens()->create(['token' => 'tok-1', 'platform' => 'android']);

        app(FirebaseNotificationService::class)->send($user, 'T', str_repeat('a', 5000), 'livraison.livree', []);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'messages:send') && mb_strlen($request['message']['notification']['body']) <= 203);
    }
}


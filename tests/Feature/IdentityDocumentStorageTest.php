<?php

namespace Tests\Feature;

use App\Models\IdentityVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IdentityDocumentStorageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    private function submit(User $user, array $files = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user, 'sanctum')->post('/api/v1/verification/submit', $files + [
            'document_type' => 'id_card',
            'document' => UploadedFile::fake()->image('cni.jpg'),
            'selfie' => UploadedFile::fake()->image('selfie.jpg'),
        ], ['Accept' => 'application/json']);
    }

    public function test_documents_are_stored_on_private_disk_only(): void
    {
        $this->submit(User::factory()->create())->assertSuccessful();

        $verification = IdentityVerification::firstOrFail();
        Storage::disk('local')->assertExists([$verification->document_path, $verification->selfie_path]);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_php_or_other_files_are_rejected(): void
    {
        $this->submit(User::factory()->create(), [
            'document' => UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;'),
        ])->assertUnprocessable();

        $this->assertDatabaseCount('identity_verifications', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_only_admins_can_view_documents(): void
    {
        $this->submit(User::factory()->create())->assertSuccessful();
        $verification = IdentityVerification::firstOrFail();
        $url = route('admin.verifications.file', [$verification, 'document']);

        $this->app['auth']->forgetGuards();
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get($url)
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($admin)->get(route('admin.verifications.file', [$verification, 'selfie']))->assertOk();
        $this->actingAs($admin)->get(route('admin.verifications.file', [$verification, 'autre']))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.verifications.show', $verification))
            ->assertOk()
            ->assertSee($url, false);
    }

    public function test_migration_moves_existing_public_documents_to_private_disk(): void
    {
        Storage::disk('public')->put('verification_documents/a.jpg', 'cni');
        Storage::disk('public')->put('verification_selfies/b.jpg', 'selfie');
        Storage::disk('public')->put('avatars/c.jpg', 'avatar');

        $migration = require database_path('migrations/2026_10_02_000002_move_identity_documents_to_private_disk.php');
        $migration->up();

        Storage::disk('local')->assertExists(['verification_documents/a.jpg', 'verification_selfies/b.jpg']);
        $this->assertSame('cni', Storage::disk('local')->get('verification_documents/a.jpg'));
        Storage::disk('public')->assertMissing(['verification_documents/a.jpg', 'verification_selfies/b.jpg']);
        Storage::disk('public')->assertExists('avatars/c.jpg');
    }
}

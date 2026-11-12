<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_an_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'creator']);
        Sanctum::actingAs($user, ['*']);

        $this->post('/api/profile/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg')], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('user.avatar', fn ($path) => str_starts_with($path, 'avatars/'));

        Storage::disk('public')->assertExists($user->fresh()->avatar);
    }

    public function test_replacing_an_avatar_deletes_the_old_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'brand']);
        Sanctum::actingAs($user, ['*']);

        $this->post('/api/profile/avatar', ['avatar' => UploadedFile::fake()->image('a.png')], ['Accept' => 'application/json']);
        $first = $user->fresh()->avatar;

        $this->post('/api/profile/avatar', ['avatar' => UploadedFile::fake()->image('b.png')], ['Accept' => 'application/json']);

        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($user->fresh()->avatar);
    }

    public function test_avatar_larger_than_2mb_is_rejected_with_a_clear_message(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create(['role' => 'creator']), ['*']);

        $this->post('/api/profile/avatar', ['avatar' => UploadedFile::fake()->image('big.jpg')->size(3000)], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['avatar'])
            ->assertJsonPath('errors.avatar.0', 'The photo must be 2 MB or smaller.');
    }

    public function test_non_image_is_rejected(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create(['role' => 'creator']), ['*']);

        $this->post('/api/profile/avatar', ['avatar' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['avatar']);
    }

    public function test_user_can_remove_their_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'creator']);
        Sanctum::actingAs($user, ['*']);

        $this->post('/api/profile/avatar', ['avatar' => UploadedFile::fake()->image('me.jpg')], ['Accept' => 'application/json']);
        $path = $user->fresh()->avatar;

        $this->deleteJson('/api/profile/avatar')->assertOk()->assertJsonPath('user.avatar', null);

        Storage::disk('public')->assertMissing($path);
    }

    public function test_guest_cannot_upload(): void
    {
        $this->postJson('/api/profile/avatar', [])->assertStatus(401);
    }
}

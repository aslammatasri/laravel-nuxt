<?php

namespace Tests\Feature\Creator;

use App\Models\CreatorProfile;
use App\Models\CreatorSocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SocialAccountControllerTest extends TestCase
{
    use RefreshDatabase;

    private function fakeIdToken(string $email): string
    {
        $header = rtrim(strtr(base64_encode('{"alg":"none"}'), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode(['email' => $email])), '+/', '-_'), '=');

        return "{$header}.{$payload}.signature";
    }

    private function fakeGoogleResponses(string $channelId = 'UC123', string $email = 'creator@gmail.com'): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fake-access-token',
                'id_token'     => $this->fakeIdToken($email),
            ]),
            'www.googleapis.com/youtube/v3/channels*' => Http::response([
                'items' => [[
                    'id' => $channelId,
                    'snippet' => [
                        'title'      => 'Test Channel',
                        'customUrl'  => '@testchannel',
                        'thumbnails' => ['default' => ['url' => 'https://example.com/thumb.jpg']],
                    ],
                    'statistics' => [
                        'subscriberCount' => '1000',
                        'viewCount'       => '50000',
                        'videoCount'      => '20',
                    ],
                    'contentDetails' => [
                        'relatedPlaylists' => ['uploads' => 'UU123'],
                    ],
                ]],
            ]),
            'www.googleapis.com/youtube/v3/playlistItems*' => Http::response(['items' => []]),
            'www.googleapis.com/youtube/v3/videos*' => Http::response(['items' => []]),
        ]);
    }

    public function test_store_verifies_and_creates_account_on_success(): void
    {
        $user = User::factory()->create(['role' => 'creator']);
        Sanctum::actingAs($user, ['*']);
        $this->fakeGoogleResponses(channelId: 'UC123', email: 'creator@gmail.com');

        $response = $this->postJson('/api/creator/social-accounts/youtube', ['code' => 'auth-code']);

        $response->assertOk();
        $this->assertDatabaseHas('creator_social_accounts', [
            'channel_id'   => 'UC123',
            'google_email' => 'creator@gmail.com',
            'platform'     => 'youtube',
        ]);
        $this->assertNotNull(CreatorSocialAccount::first()->verified_at);
    }

    public function test_store_rejects_channel_already_claimed_by_another_creator(): void
    {
        $otherUser = User::factory()->create(['role' => 'creator']);
        $otherProfile = CreatorProfile::create(['user_id' => $otherUser->id]);
        CreatorSocialAccount::create([
            'creator_profile_id' => $otherProfile->id,
            'platform'           => 'youtube',
            'channel_id'         => 'UC123',
            'handle'             => 'testchannel',
        ]);

        $user = User::factory()->create(['role' => 'creator']);
        Sanctum::actingAs($user, ['*']);
        $this->fakeGoogleResponses(channelId: 'UC123');

        $response = $this->postJson('/api/creator/social-accounts/youtube', ['code' => 'auth-code']);

        $response->assertStatus(422)
            ->assertJson(['message' => 'This channel is already linked to another creator account']);
        $this->assertSame(1, CreatorSocialAccount::where('channel_id', 'UC123')->count());
    }

    public function test_store_returns_error_when_token_exchange_fails(): void
    {
        $user = User::factory()->create(['role' => 'creator']);
        Sanctum::actingAs($user, ['*']);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([], 400),
        ]);

        $response = $this->postJson('/api/creator/social-accounts/youtube', ['code' => 'bad-code']);

        $response->assertStatus(502);
        $this->assertDatabaseCount('creator_social_accounts', 0);
    }

    public function test_store_returns_error_when_no_channel_on_google_account(): void
    {
        $user = User::factory()->create(['role' => 'creator']);
        Sanctum::actingAs($user, ['*']);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fake-access-token',
                'id_token'     => $this->fakeIdToken('creator@gmail.com'),
            ]),
            'www.googleapis.com/youtube/v3/channels*' => Http::response(['items' => []]),
        ]);

        $response = $this->postJson('/api/creator/social-accounts/youtube', ['code' => 'auth-code']);

        $response->assertStatus(404)
            ->assertJson(['message' => 'No YouTube channel found on this Google account']);
    }

    public function test_store_requires_code(): void
    {
        $user = User::factory()->create(['role' => 'creator']);
        Sanctum::actingAs($user, ['*']);
        Http::fake();

        $response = $this->postJson('/api/creator/social-accounts/youtube', []);

        $response->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_sync_looks_up_channel_by_id_not_handle(): void
    {
        $user = User::factory()->create(['role' => 'creator']);
        $profile = CreatorProfile::create(['user_id' => $user->id]);
        $account = CreatorSocialAccount::create([
            'creator_profile_id' => $profile->id,
            'platform'           => 'youtube',
            'channel_id'         => 'UC123',
            'handle'             => 'testchannel',
        ]);
        Sanctum::actingAs($user, ['*']);

        Http::fake([
            'www.googleapis.com/youtube/v3/channels*' => Http::response([
                'items' => [[
                    'id' => 'UC123',
                    'snippet' => ['title' => 'Test Channel', 'thumbnails' => []],
                    'statistics' => ['subscriberCount' => '2000', 'viewCount' => '60000', 'videoCount' => '25'],
                    'contentDetails' => ['relatedPlaylists' => ['uploads' => 'UU123']],
                ]],
            ]),
            'www.googleapis.com/youtube/v3/playlistItems*' => Http::response(['items' => []]),
        ]);

        $response = $this->postJson("/api/creator/social-accounts/{$account->id}/sync");

        $response->assertOk();
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/channels')
                && str_contains($request->url(), 'id=UC123')
                && ! str_contains($request->url(), 'forHandle');
        });
    }
}

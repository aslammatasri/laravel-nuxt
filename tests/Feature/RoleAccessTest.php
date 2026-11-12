<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_protected_route(): void
    {
        $this->getJson('/api/admin/users')->assertStatus(401);
    }

    public function test_creator_cannot_access_admin_route(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'creator']), ['*']);

        $this->getJson('/api/admin/users')->assertStatus(403);
    }

    public function test_creator_cannot_access_brand_route(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'creator']), ['*']);

        $this->getJson('/api/brand/applications')->assertStatus(403);
    }

    public function test_brand_cannot_access_creator_route(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'brand']), ['*']);

        $this->getJson('/api/creator/applications')->assertStatus(403);
    }

    public function test_admin_can_access_admin_route(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']), ['*']);

        $this->getJson('/api/admin/users')->assertOk();
    }
}

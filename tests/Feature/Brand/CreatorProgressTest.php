<?php

namespace Tests\Feature\Brand;

use App\Models\CollaborationTask;
use App\Models\Product;
use App\Models\ProductApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreatorProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_sees_only_own_approved_creators_with_progress(): void
    {
        $brand = User::factory()->create(['role' => 'brand']);
        $otherBrand = User::factory()->create(['role' => 'brand']);
        $creator = User::factory()->create(['role' => 'creator']);

        $mine = Product::create(['brand_id' => $brand->id, 'name' => 'Mine', 'status' => 'active']);
        $theirs = Product::create(['brand_id' => $otherBrand->id, 'name' => 'Theirs', 'status' => 'active']);

        $app = ProductApplication::create(['product_id' => $mine->id, 'creator_id' => $creator->id, 'status' => 'approved']);
        ProductApplication::create(['product_id' => $theirs->id, 'creator_id' => $creator->id, 'status' => 'approved']);
        CollaborationTask::create(['application_id' => $app->id, 'title' => 'A', 'completed_at' => now()]);
        CollaborationTask::create(['application_id' => $app->id, 'title' => 'B']);

        Sanctum::actingAs($brand, ['*']);

        $res = $this->getJson('/api/brand/creators')->assertOk();
        $this->assertCount(1, $res->json('data'));
        $this->assertCount(1, $res->json('data.0.collaborations'));
        $this->assertCount(2, $res->json('data.0.collaborations.0.tasks'));
    }

    public function test_creator_role_is_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'creator']), ['*']);
        $this->getJson('/api/brand/creators')->assertForbidden();
    }
}

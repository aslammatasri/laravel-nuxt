<?php

namespace Tests\Feature\Creator;

use App\Models\Product;
use App\Models\ProductApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CollaborationChecklistTest extends TestCase
{
    use RefreshDatabase;

    private function collab(User $creator, array $product = []): ProductApplication
    {
        $brand = User::factory()->create(['role' => 'brand']);
        $p = Product::create($product + ['brand_id' => $brand->id, 'name' => 'P', 'status' => 'active']);

        return ProductApplication::create([
            'product_id' => $p->id, 'creator_id' => $creator->id, 'status' => 'approved',
        ]);
    }

    public function test_checklist_is_seeded_from_campaign_once(): void
    {
        $creator = User::factory()->create(['role' => 'creator']);
        $a = $this->collab($creator, ['posts_required' => 3, 'free_sample' => true]);
        Sanctum::actingAs($creator, ['*']);

        $res = $this->getJson('/api/creator/collaborations')->assertOk();
        $this->assertCount(4, $res->json('data.0.tasks'));

        $this->getJson('/api/creator/collaborations');
        $this->assertDatabaseCount('collaboration_tasks', 4);
    }

    public function test_creator_can_add_complete_and_delete_task(): void
    {
        $creator = User::factory()->create(['role' => 'creator']);
        $a = $this->collab($creator);
        Sanctum::actingAs($creator, ['*']);

        $id = $this->postJson("/api/creator/collaborations/{$a->id}/tasks", ['title' => 'Go live', 'type' => 'live'])
            ->assertCreated()->json('data.id');

        $this->patchJson("/api/creator/collaborations/{$a->id}/tasks/{$id}", ['done' => true])
            ->assertOk()->assertJsonPath('data.done', true);

        $this->deleteJson("/api/creator/collaborations/{$a->id}/tasks/{$id}")->assertOk();
        $this->assertDatabaseMissing('collaboration_tasks', ['id' => $id]);
    }

    public function test_creator_cannot_touch_another_creators_collab(): void
    {
        $owner = User::factory()->create(['role' => 'creator']);
        $other = User::factory()->create(['role' => 'creator']);
        $a = $this->collab($owner);
        Sanctum::actingAs($other, ['*']);

        $this->postJson("/api/creator/collaborations/{$a->id}/tasks", ['title' => 'x'])->assertNotFound();
    }
}

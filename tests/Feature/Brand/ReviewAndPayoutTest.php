<?php

namespace Tests\Feature\Brand;

use App\Models\CollaborationTask;
use App\Models\Product;
use App\Models\ProductApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewAndPayoutTest extends TestCase
{
    use RefreshDatabase;

    private function collab(float $fee = 200): array
    {
        $brand = User::factory()->create(['role' => 'brand']);
        $creator = User::factory()->create(['role' => 'creator']);
        $p = Product::create(['brand_id' => $brand->id, 'name' => 'P', 'status' => 'active', 'flat_fee' => $fee]);
        $app = ProductApplication::create([
            'product_id' => $p->id, 'creator_id' => $creator->id, 'status' => 'approved', 'checklist_seeded' => true,
        ]);
        $video = CollaborationTask::create(['application_id' => $app->id, 'title' => 'Video', 'type' => 'video', 'completed_at' => now()]);
        $sample = CollaborationTask::create(['application_id' => $app->id, 'title' => 'Sample', 'type' => 'sample']);

        return [$brand, $creator, $app, $video, $sample];
    }

    public function test_fee_is_due_only_after_all_deliverables_are_approved(): void
    {
        [$brand, , $app, $video] = $this->collab();
        Sanctum::actingAs($brand, ['*']);

        $this->patchJson("/api/brand/applications/{$app->id}/payout")->assertStatus(422);

        $this->patchJson("/api/brand/tasks/{$video->id}/review", ['status' => 'approved'])->assertOk();
        $this->assertSame('due', $app->fresh(['product', 'tasks'])->feeStatus()); // sample task is ignored

        $this->patchJson("/api/brand/applications/{$app->id}/payout", ['reference' => 'TXN-1'])
            ->assertOk()->assertJsonPath('data.status', 'paid');
        $this->patchJson("/api/brand/applications/{$app->id}/payout")->assertStatus(422);
    }

    public function test_marking_paid_notifies_creator_and_brand_list_shows_fee(): void
    {
        [$brand, $creator, $app, $video] = $this->collab(3500);
        Sanctum::actingAs($brand, ['*']);

        $this->patchJson("/api/brand/tasks/{$video->id}/review", ['status' => 'approved'])->assertOk();
        $this->getJson('/api/brand/creators')
            ->assertJsonPath('data.0.collaborations.0.fee.amount', 3500)
            ->assertJsonPath('data.0.collaborations.0.fee.status', 'due');

        $this->patchJson("/api/brand/applications/{$app->id}/payout", ['reference' => 'TXN-9'])->assertOk();

        $n = $creator->notifications()->where('type', \App\Notifications\PayoutSent::class)->first();
        $this->assertNotNull($n);
        $this->assertSame('/creator/earnings', $n->data['url']);
        $this->assertStringContainsString('TXN-9', $n->data['message']);
    }

    public function test_changes_requested_needs_a_note_and_creator_edit_resets_review(): void
    {
        [$brand, $creator, $app, $video] = $this->collab();
        Sanctum::actingAs($brand, ['*']);

        $this->patchJson("/api/brand/tasks/{$video->id}/review", ['status' => 'changes_requested'])->assertStatus(422);
        $this->patchJson("/api/brand/tasks/{$video->id}/review", ['status' => 'changes_requested', 'note' => 'Show the logo'])->assertOk();

        // Rejected task is unchecked and the creator is notified
        $this->assertNull($video->fresh()->completed_at);
        $this->assertSame('Changes requested', $creator->notifications()->first()->data['title']);

        Sanctum::actingAs($creator, ['*']);
        $this->patchJson("/api/creator/collaborations/{$app->id}/tasks/{$video->id}", ['link' => 'https://youtu.be/x'])
            ->assertOk()->assertJsonPath('data.review', 'pending');
    }

    public function test_brand_cannot_review_unfinished_or_foreign_tasks(): void
    {
        [$brand, , , $video, $sample] = $this->collab();
        $video->update(['completed_at' => null]);
        Sanctum::actingAs($brand, ['*']);
        $this->patchJson("/api/brand/tasks/{$video->id}/review", ['status' => 'approved'])->assertStatus(422);
        $this->patchJson("/api/brand/tasks/{$sample->id}/review", ['status' => 'approved'])->assertStatus(422);

        Sanctum::actingAs(User::factory()->create(['role' => 'brand']), ['*']);
        $this->patchJson("/api/brand/tasks/{$video->id}/review", ['status' => 'approved'])->assertNotFound();
    }

    public function test_creator_payout_details_round_trip_and_brand_sees_them(): void
    {
        [$brand, $creator] = $this->collab();
        Sanctum::actingAs($creator, ['*']);

        $this->putJson('/api/creator/payout-details', ['bank' => 'Maybank', 'account_name' => 'Aisyah', 'account_number' => '1122334455'])->assertOk();
        $this->getJson('/api/creator/payout-details')->assertJsonPath('data.bank', 'Maybank');
        $this->putJson('/api/creator/payout-details', ['bank' => 'x'])->assertStatus(422);

        Sanctum::actingAs($brand, ['*']);
        $this->getJson('/api/brand/creators')->assertJsonPath('data.0.payout.account_number', '1122334455');
    }
}

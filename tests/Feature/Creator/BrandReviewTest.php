<?php

namespace Tests\Feature\Creator;

use App\Models\CollaborationTask;
use App\Models\Product;
use App\Models\ProductApplication;
use App\Models\User;
use App\Notifications\BrandReviewed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BrandReviewTest extends TestCase
{
    use RefreshDatabase;

    private function collab(bool $finished = true): array
    {
        $brand = User::factory()->create(['role' => 'brand']);
        $creator = User::factory()->create(['role' => 'creator']);
        $p = Product::create(['brand_id' => $brand->id, 'name' => 'Sunscreen', 'status' => 'active', 'flat_fee' => 200]);
        $app = ProductApplication::create(['product_id' => $p->id, 'creator_id' => $creator->id, 'status' => 'approved', 'checklist_seeded' => true]);
        CollaborationTask::create([
            'application_id' => $app->id, 'title' => 'Video', 'type' => 'video',
            'completed_at' => $finished ? now() : null, 'review_status' => $finished ? 'approved' : 'pending',
        ]);

        return [$brand, $creator, $app];
    }

    public function test_creator_can_review_after_work_is_approved_and_edit_it(): void
    {
        [$brand, $creator, $app] = $this->collab();
        Sanctum::actingAs($creator, ['*']);

        $this->getJson('/api/creator/collaborations')->assertJsonPath('data.0.can_review', true)->assertJsonPath('data.0.review', null);

        $this->putJson("/api/creator/collaborations/{$app->id}/review", ['rating' => 5, 'body' => ' Paid fast, clear brief. '])
            ->assertOk()->assertJsonPath('data.rating', 5)->assertJsonPath('data.body', 'Paid fast, clear brief.');
        $this->assertCount(1, $brand->notifications->where('type', BrandReviewed::class));

        // editing updates the same row and does not notify again
        $this->putJson("/api/creator/collaborations/{$app->id}/review", ['rating' => 4])->assertOk();
        $this->assertDatabaseCount('brand_reviews', 1);
        $this->assertDatabaseHas('brand_reviews', ['application_id' => $app->id, 'rating' => 4, 'body' => null]);
        $this->assertCount(1, $brand->fresh()->notifications);

        $this->deleteJson("/api/creator/collaborations/{$app->id}/review")->assertOk();
        $this->assertDatabaseCount('brand_reviews', 0);
    }

    public function test_cannot_review_before_work_is_done_or_paid_and_validates_rating(): void
    {
        [, $creator, $app] = $this->collab(finished: false);
        Sanctum::actingAs($creator, ['*']);

        $this->putJson("/api/creator/collaborations/{$app->id}/review", ['rating' => 5])->assertStatus(422);

        $app->update(['paid_at' => now()]); // paid unlocks it even if tasks are not all approved
        $this->putJson("/api/creator/collaborations/{$app->id}/review", ['rating' => 6])->assertStatus(422);
        $this->putJson("/api/creator/collaborations/{$app->id}/review", ['rating' => 3])->assertOk();
    }

    public function test_only_the_collab_owner_can_review(): void
    {
        [, , $app] = $this->collab();
        Sanctum::actingAs(User::factory()->create(['role' => 'creator']), ['*']);
        $this->putJson("/api/creator/collaborations/{$app->id}/review", ['rating' => 1])->assertNotFound();
    }

    public function test_other_creators_see_average_and_reviews_on_the_brand_page(): void
    {
        [$brand, $creator, $app] = $this->collab();
        Sanctum::actingAs($creator, ['*']);
        $this->putJson("/api/creator/collaborations/{$app->id}/review", ['rating' => 4, 'body' => 'Good experience.'])->assertOk();

        Sanctum::actingAs(User::factory()->create(['role' => 'creator']), ['*']);
        $res = $this->getJson("/api/creator/brands/{$brand->id}")->assertOk()
            ->assertJsonPath('data.rating_average', 4)
            ->assertJsonPath('data.rating_count', 1)
            ->assertJsonPath('data.reviews.0.body', 'Good experience.')
            ->assertJsonPath('data.reviews.0.campaign', 'Sunscreen')
            ->assertJsonPath('data.reviews.0.mine', false);
        $this->assertStringNotContainsString($creator->email, $res->getContent());
    }

    public function test_brand_sees_its_own_reviews_and_summary_only(): void
    {
        [$brand, $creator, $app] = $this->collab();
        Sanctum::actingAs($creator, ['*']);
        $this->putJson("/api/creator/collaborations/{$app->id}/review", ['rating' => 5, 'body' => 'Great'])->assertOk();

        Sanctum::actingAs($brand, ['*']);
        $this->getJson('/api/brand/reviews')->assertOk()
            ->assertJsonPath('average', 5)
            ->assertJsonPath('count', 1)
            ->assertJsonPath('distribution.5', 1)
            ->assertJsonPath('distribution.1', 0)
            ->assertJsonPath('data.0.body', 'Great')
            ->assertJsonPath('data.0.campaign', 'Sunscreen');

        Sanctum::actingAs(User::factory()->create(['role' => 'brand']), ['*']);
        $this->getJson('/api/brand/reviews')->assertJsonPath('count', 0)->assertJsonCount(0, 'data');

        Sanctum::actingAs($creator, ['*']);
        $this->getJson('/api/brand/reviews')->assertForbidden();
    }
}

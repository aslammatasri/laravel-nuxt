<?php

namespace Tests\Feature\Brand;

use App\Models\BrandProfile;
use App\Models\BrandReview;
use App\Models\CollaborationTask;
use App\Models\Product;
use App\Models\ProductApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_reports_actions_money_rating_and_setup(): void
    {
        $brand = User::factory()->create(['role' => 'brand']);
        $creator = User::factory()->create(['role' => 'creator']);
        $p = Product::create(['brand_id' => $brand->id, 'name' => 'Sunscreen', 'status' => 'active', 'flat_fee' => 500]);

        // collab 1: all deliverables approved, unpaid -> fee due
        $due = ProductApplication::create(['product_id' => $p->id, 'creator_id' => $creator->id, 'status' => 'approved', 'checklist_seeded' => true]);
        CollaborationTask::create(['application_id' => $due->id, 'title' => 'V', 'type' => 'video', 'completed_at' => now(), 'review_status' => 'approved']);

        // collab 2: done but waiting for review, one overdue task
        $other = User::factory()->create(['role' => 'creator']);
        $wait = ProductApplication::create(['product_id' => $p->id, 'creator_id' => $other->id, 'status' => 'approved', 'checklist_seeded' => true]);
        CollaborationTask::create(['application_id' => $wait->id, 'title' => 'V', 'type' => 'video', 'completed_at' => now(), 'review_status' => 'pending']);
        CollaborationTask::create(['application_id' => $wait->id, 'title' => 'P', 'type' => 'photo', 'due_date' => now()->subDays(2)]);

        // collab 3: already paid this month
        $third = User::factory()->create(['role' => 'creator']);
        ProductApplication::create(['product_id' => $p->id, 'creator_id' => $third->id, 'status' => 'approved', 'checklist_seeded' => true, 'paid_at' => now()]);

        // one pending application and one review
        ProductApplication::create(['product_id' => $p->id, 'creator_id' => User::factory()->create(['role' => 'creator'])->id, 'status' => 'pending']);
        BrandReview::create(['application_id' => $due->id, 'creator_id' => $creator->id, 'brand_id' => $brand->id, 'rating' => 4, 'body' => 'Good']);
        BrandProfile::create(['user_id' => $brand->id, 'company_name' => 'Acme', 'about' => 'We make things']);

        Sanctum::actingAs($brand, ['*']);
        $this->getJson('/api/brand/dashboard/stats')->assertOk()
            ->assertJsonPath('actions.pending_applications', 1)
            ->assertJsonPath('actions.deliverables_to_review', 1)
            ->assertJsonPath('actions.fees_due.count', 1)
            ->assertJsonPath('actions.fees_due.amount', 500)
            ->assertJsonPath('actions.overdue_creators', 1)
            ->assertJsonPath('spend.due', 500)
            ->assertJsonPath('spend.paid_this_month', 500)
            ->assertJsonPath('spend.paid_total', 500)
            ->assertJsonPath('rating.average', 4)
            ->assertJsonPath('rating.latest.body', 'Good')
            ->assertJsonPath('setup.has_about', true)
            ->assertJsonPath('setup.has_campaign', true)
            ->assertJsonPath('setup.has_creator', true);
    }

    public function test_new_brand_dashboard_is_empty_and_safe(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'brand']), ['*']);
        $this->getJson('/api/brand/dashboard/stats')->assertOk()
            ->assertJsonPath('actions.fees_due.amount', 0)
            ->assertJsonPath('rating.average', null)
            ->assertJsonPath('setup.has_campaign', false);
    }
}

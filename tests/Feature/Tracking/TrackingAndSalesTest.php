<?php

namespace Tests\Feature\Tracking;

use App\Models\Product;
use App\Models\ProductApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TrackingAndSalesTest extends TestCase
{
    use RefreshDatabase;

    private function setup_collab(array $product = []): array
    {
        $brand = User::factory()->create(['role' => 'brand']);
        $creator = User::factory()->create(['role' => 'creator']);
        $p = Product::create($product + [
            'brand_id' => $brand->id, 'name' => 'P', 'status' => 'active',
            'product_url' => 'https://shop.example.com/p', 'commission_rate' => '10', 'commission_type' => 'percentage',
        ]);
        $app = ProductApplication::create(['product_id' => $p->id, 'creator_id' => $creator->id, 'status' => 'approved']);

        return [$brand, $creator, $app];
    }

    public function test_tracked_link_redirects_and_dedupes_clicks(): void
    {
        [, , $app] = $this->setup_collab();
        $code = $app->ensureTrackingCode();

        $this->get("/r/{$code}")->assertRedirect('https://shop.example.com/p');
        $this->get("/r/{$code}")->assertRedirect('https://shop.example.com/p');

        $this->assertDatabaseCount('link_clicks', 1);
    }

    public function test_unknown_code_or_missing_product_url_is_404(): void
    {
        $this->get('/r/nope')->assertNotFound();

        [, , $app] = $this->setup_collab(['product_url' => null]);
        $this->get('/r/' . $app->ensureTrackingCode())->assertNotFound();
    }

    public function test_brand_logs_sale_with_percentage_commission(): void
    {
        [$brand, , $app] = $this->setup_collab();
        Sanctum::actingAs($brand, ['*']);

        $this->postJson("/api/brand/applications/{$app->id}/sales", ['amount' => 250, 'order_ref' => 'A1'])
            ->assertCreated()
            ->assertJsonPath('data.commission', 25)
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_fixed_commission_and_paid_and_reverse_flow(): void
    {
        [$brand, $creator, $app] = $this->setup_collab(['commission_rate' => '7', 'commission_type' => 'fixed']);
        Sanctum::actingAs($brand, ['*']);

        $id = $this->postJson("/api/brand/applications/{$app->id}/sales", ['amount' => 100])->json('data.id');
        $this->assertSame(7.0, (float) $app->fresh()->salesSummary()['owed']);

        $this->patchJson("/api/brand/sales/{$id}/paid")->assertOk()->assertJsonPath('data.status', 'paid');
        $this->patchJson("/api/brand/sales/{$id}/paid")->assertStatus(422);
        $this->patchJson("/api/brand/sales/{$id}/reverse")->assertOk();
        $this->assertSame(0, $app->fresh()->salesSummary()['count']);

        Sanctum::actingAs($creator, ['*']);
        $this->getJson('/api/creator/earnings')->assertOk()->assertJsonPath('campaigns.0.commission.owed', 0);
    }

    public function test_other_brand_cannot_log_sale(): void
    {
        [, , $app] = $this->setup_collab();
        Sanctum::actingAs(User::factory()->create(['role' => 'brand']), ['*']);

        $this->postJson("/api/brand/applications/{$app->id}/sales", ['amount' => 50])->assertNotFound();
    }

    public function test_creator_earnings_totals(): void
    {
        [$brand, $creator, $app] = $this->setup_collab();
        Sanctum::actingAs($brand, ['*']);
        $this->postJson("/api/brand/applications/{$app->id}/sales", ['amount' => 100]);
        $this->postJson("/api/brand/applications/{$app->id}/sales", ['amount' => 300]);

        Sanctum::actingAs($creator, ['*']);
        $this->getJson('/api/creator/earnings')
            ->assertOk()
            ->assertJsonPath('campaigns.0.commission.owed', 40)
            ->assertJsonPath('campaigns.0.commission.count', 2);
    }
}

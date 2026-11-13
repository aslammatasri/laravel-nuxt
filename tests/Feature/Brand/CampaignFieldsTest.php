<?php

namespace Tests\Feature\Brand;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CampaignFieldsTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name'            => 'Sunscreen SPF50',
            'description'     => 'Lightweight sunscreen',
            'price'           => 49.9,
            'category'        => 'beauty',
            'commission_rate' => 10,
            'commission_type' => 'percentage',
            'campaign_start'  => now()->addDay()->toDateString(),
            'campaign_end'    => now()->addDays(30)->toDateString(),
        ], $overrides);
    }

    public function test_brand_can_create_a_campaign_with_brief_requirements_and_perks(): void
    {
        $brand = User::factory()->create(['role' => 'brand']);
        Sanctum::actingAs($brand, ['*']);

        $this->postJson('/api/brand/products', $this->payload([
            'platforms'            => ['youtube', 'tiktok'],
            'content_types'        => ['review', 'unboxing'],
            'posts_required'       => 2,
            'brief'                => 'Show the texture and the SPF label.',
            'hashtags'             => '#skintific #spf50',
            'product_url'          => 'https://shop.example.com/spf50',
            'min_subscribers'      => 5000,
            'target_niches'        => ['beauty', 'lifestyle'],
            'free_sample'          => true,
            'flat_fee'             => 150,
            'application_deadline' => now()->addDays(10)->toDateString(),
        ]))
            ->assertCreated()
            ->assertJsonPath('platforms', ['youtube', 'tiktok'])
            ->assertJsonPath('free_sample', true)
            ->assertJsonPath('min_subscribers', 5000);

        $product = Product::first();
        $this->assertSame(['beauty', 'lifestyle'], $product->target_niches);
        $this->assertSame(2, $product->posts_required);
    }

    public function test_campaign_still_works_without_any_new_fields(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'brand']), ['*']);

        $this->postJson('/api/brand/products', $this->payload())->assertCreated();
    }

    public function test_invalid_values_get_clear_messages(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'brand']), ['*']);

        $this->postJson('/api/brand/products', $this->payload([
            'platforms'            => ['myspace'],
            'product_url'          => 'not a url',
            'application_deadline' => now()->addDays(60)->toDateString(),
        ]))
            ->assertStatus(422)
            ->assertJsonPath('errors.product_url.0', 'Enter a full link, for example https://shop.example.com/product.')
            ->assertJsonPath('errors.application_deadline.0', 'Applications must close on or before the campaign end date.')
            ->assertJsonValidationErrors(['platforms.0']);
    }

    public function test_creator_cannot_apply_after_the_application_deadline(): void
    {
        $brand = User::factory()->create(['role' => 'brand']);
        $product = Product::create($this->payload([
            'brand_id'             => $brand->id,
            'status'               => 'active',
            'application_deadline' => now()->subDay()->toDateString(),
        ]));

        Sanctum::actingAs(User::factory()->create(['role' => 'creator']), ['*']);

        $this->postJson("/api/creator/products/{$product->id}/apply", ['pitch_message' => str_repeat('I love this product. ', 3)])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Applications for this campaign have closed');
    }
}

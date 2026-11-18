<?php

namespace Tests\Feature\Creator;

use App\Models\BrandProfile;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BrandProfileTest extends TestCase
{
    use RefreshDatabase;

    private function brand(): User
    {
        $brand = User::factory()->create(['role' => 'brand']);
        BrandProfile::create([
            'user_id' => $brand->id, 'company_name' => 'Acme Sdn Bhd', 'industry' => 'beauty',
            'ssm_no' => '202301012345', 'is_verified' => true, 'verification_note' => 'internal note',
        ]);

        return $brand;
    }

    public function test_creator_sees_public_brand_profile_without_private_fields(): void
    {
        $brand = $this->brand();
        Product::create(['brand_id' => $brand->id, 'name' => 'Live', 'status' => 'active', 'flat_fee' => 100]);
        Product::create(['brand_id' => $brand->id, 'name' => 'Paused', 'status' => 'paused', 'flat_fee' => 100]);
        Sanctum::actingAs(User::factory()->create(['role' => 'creator']), ['*']);

        $res = $this->getJson("/api/creator/brands/{$brand->id}")->assertOk();

        $res->assertJsonPath('data.company_name', 'Acme Sdn Bhd')
            ->assertJsonPath('data.is_verified', true)
            ->assertJsonCount(1, 'data.campaigns')
            ->assertJsonPath('data.campaigns.0.name', 'Live');
        $this->assertStringNotContainsString('202301012345', $res->getContent());
        $this->assertStringNotContainsString('internal note', $res->getContent());
        $this->assertStringNotContainsString($brand->email, $res->getContent());
    }

    public function test_non_brand_and_suspended_brand_are_404_and_brands_are_blocked(): void
    {
        $creator = User::factory()->create(['role' => 'creator']);
        $suspended = $this->brand();
        $suspended->forceFill(["suspended_at" => now()])->save();
        Sanctum::actingAs($creator, ['*']);

        $this->getJson("/api/creator/brands/{$creator->id}")->assertNotFound();
        $this->getJson("/api/creator/brands/{$suspended->id}")->assertNotFound();

        Sanctum::actingAs($this->brand(), ['*']);
        $this->getJson("/api/creator/brands/{$suspended->id}")->assertForbidden();
    }

    public function test_brand_about_is_saved_without_touching_verification_and_creators_read_it(): void
    {
        $brand = $this->brand();
        BrandProfile::where('user_id', $brand->id)->update(['verification_status' => 'verified']);
        Sanctum::actingAs($brand->fresh(), ['*']);

        $this->putJson('/api/brand/about', ['about' => '  We make skincare in KL.  '])->assertOk();
        $this->getJson('/api/brand/about')->assertJsonPath('about', 'We make skincare in KL.');
        $profile = BrandProfile::where('user_id', $brand->id)->first();
        $this->assertTrue($profile->is_verified);
        $this->assertSame('verified', $profile->verification_status);

        $this->putJson('/api/brand/about', ['about' => str_repeat('a', 1001)])->assertStatus(422);

        Sanctum::actingAs(User::factory()->create(['role' => 'creator']), ['*']);
        $this->getJson("/api/creator/brands/{$brand->id}")->assertJsonPath('data.about', 'We make skincare in KL.');
        $this->putJson('/api/brand/about', ['about' => 'x'])->assertForbidden();
    }

    public function test_marketplace_does_not_leak_brand_email(): void
    {
        $brand = $this->brand();
        $p = Product::create(['brand_id' => $brand->id, 'name' => 'Live', 'status' => 'active', 'flat_fee' => 100]);
        Sanctum::actingAs(User::factory()->create(['role' => 'creator']), ['*']);

        foreach (['/api/creator/marketplace', "/api/creator/marketplace/{$p->id}"] as $url) {
            $body = $this->getJson($url)->assertOk()->getContent();
            $this->assertStringContainsString('"is_verified":true', $body);
            $this->assertStringNotContainsString($brand->email, $body);
        }
    }
}

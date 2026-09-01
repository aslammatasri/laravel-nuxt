<?php

namespace Tests\Feature\Creator;

use App\Models\Product;
use App\Models\ProductApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApplicationControllerTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(): Product
    {
        $brand = User::factory()->create(['role' => 'brand']);

        return Product::create([
            'brand_id' => $brand->id,
            'name'     => 'Test Product',
            'status'   => 'active',
        ]);
    }

    public function test_pitch_message_is_saved_on_apply(): void
    {
        Notification::fake();

        $creator = User::factory()->create(['role' => 'creator']);
        $product = $this->makeProduct();

        Sanctum::actingAs($creator, ['*']);

        $pitch = 'I would love to promote this product to my audience.';
        $response = $this->postJson("/api/creator/products/{$product->id}/apply", [
            'pitch_message' => $pitch,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('products_application', [
            'creator_id'    => $creator->id,
            'product_id'    => $product->id,
            'pitch_message' => $pitch,
        ]);
    }

    public function test_creator_cannot_apply_twice_while_pending(): void
    {
        Notification::fake();

        $creator = User::factory()->create(['role' => 'creator']);
        $product = $this->makeProduct();

        ProductApplication::create([
            'creator_id' => $creator->id,
            'product_id' => $product->id,
            'status'     => 'pending',
        ]);

        Sanctum::actingAs($creator, ['*']);

        $response = $this->postJson("/api/creator/products/{$product->id}/apply", [
            'pitch_message' => str_repeat('a', 25),
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'You have already applied to this product']);
        $this->assertSame(1, ProductApplication::where('product_id', $product->id)->count());
    }

    public function test_creator_cannot_apply_twice_while_approved(): void
    {
        Notification::fake();

        $creator = User::factory()->create(['role' => 'creator']);
        $product = $this->makeProduct();

        ProductApplication::create([
            'creator_id' => $creator->id,
            'product_id' => $product->id,
            'status'     => 'approved',
        ]);

        Sanctum::actingAs($creator, ['*']);

        $response = $this->postJson("/api/creator/products/{$product->id}/apply", [
            'pitch_message' => str_repeat('a', 25),
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'You have already applied to this product']);
    }

    public function test_creator_can_reapply_after_rejection(): void
    {
        Notification::fake();

        $creator = User::factory()->create(['role' => 'creator']);
        $product = $this->makeProduct();

        ProductApplication::create([
            'creator_id' => $creator->id,
            'product_id' => $product->id,
            'status'     => 'rejected',
        ]);

        Sanctum::actingAs($creator, ['*']);

        $response = $this->postJson("/api/creator/products/{$product->id}/apply", [
            'pitch_message' => str_repeat('a', 25),
        ]);

        $response->assertStatus(201);
        $this->assertSame(2, ProductApplication::where('product_id', $product->id)->count());
        $this->assertSame(
            1,
            ProductApplication::where('product_id', $product->id)->where('status', 'pending')->count()
        );
    }
}

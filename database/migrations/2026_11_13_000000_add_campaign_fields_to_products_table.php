<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // What the creator must do
            $table->json('platforms')->nullable();
            $table->json('content_types')->nullable();
            $table->unsignedTinyInteger('posts_required')->default(1);
            $table->text('brief')->nullable();
            $table->string('hashtags')->nullable();
            $table->string('product_url')->nullable();

            // Who can apply
            $table->unsignedInteger('min_subscribers')->nullable();
            $table->json('target_niches')->nullable();

            // What the creator gets besides commission
            $table->boolean('free_sample')->default(false);
            $table->decimal('flat_fee', 10, 2)->nullable();

            // Applications close before the campaign ends
            $table->date('application_deadline')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'platforms', 'content_types', 'posts_required', 'brief', 'hashtags', 'product_url',
                'min_subscribers', 'target_niches', 'free_sample', 'flat_fee', 'application_deadline',
            ]);
        });
    }
};

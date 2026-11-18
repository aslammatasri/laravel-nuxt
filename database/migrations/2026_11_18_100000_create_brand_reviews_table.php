<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_reviews', function (Blueprint $table) {
            $table->id();
            // One review per collaboration, so a creator reviews a brand once per campaign
            $table->foreignId('application_id')->unique()->constrained('products_application')->cascadeOnDelete();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('body')->nullable();
            $table->timestamps();

            $table->index(['brand_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_reviews');
    }
};

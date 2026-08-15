<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('creator_social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_profile_id')->constrained('creator_profiles')->cascadeOnDelete();
            $table->string('platform');
            $table->string('handle');
            $table->string('channel_id')->nullable();
            $table->string('title')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->unsignedBigInteger('subscriber_count')->nullable();
            $table->unsignedBigInteger('view_count')->nullable();
            $table->unsignedInteger('video_count')->nullable();
            $table->unsignedInteger('avg_recent_views')->nullable();
            $table->unsignedInteger('avg_recent_likes')->nullable();
            $table->unsignedInteger('avg_recent_comments')->nullable();
            $table->decimal('engagement_rate', 5, 2)->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_error')->nullable();
            $table->timestamps();

            $table->unique(['creator_profile_id', 'platform']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_social_accounts');
    }
};

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
        Schema::table('creator_social_accounts', function (Blueprint $table) {
            $table->string('google_email')->nullable()->after('handle');
            $table->timestamp('verified_at')->nullable()->after('sync_error');
            $table->unique('channel_id');
        });

        Schema::table('creator_social_accounts', function (Blueprint $table) {
            $table->dropColumn('handle');
        });

        Schema::table('creator_social_accounts', function (Blueprint $table) {
            $table->string('handle')->nullable()->after('platform');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('creator_social_accounts', function (Blueprint $table) {
            $table->dropUnique(['channel_id']);
            $table->dropColumn(['google_email', 'verified_at']);
        });

        Schema::table('creator_social_accounts', function (Blueprint $table) {
            $table->dropColumn('handle');
        });

        Schema::table('creator_social_accounts', function (Blueprint $table) {
            $table->string('handle')->after('platform');
        });
    }
};

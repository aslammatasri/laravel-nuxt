<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collaboration_tasks', function (Blueprint $table) {
            $table->string('review_status', 20)->default('pending'); // pending, approved, changes_requested
            $table->string('review_note', 500)->nullable();
            $table->timestamp('reviewed_at')->nullable();
        });

        Schema::table('products_application', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable();   // flat fee paid
            $table->string('payment_ref', 100)->nullable();
        });

        Schema::table('creator_profiles', function (Blueprint $table) {
            $table->string('payout_bank', 80)->nullable();
            $table->string('payout_account_name', 120)->nullable();
            $table->string('payout_account_number', 40)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('collaboration_tasks', fn (Blueprint $t) => $t->dropColumn(['review_status', 'review_note', 'reviewed_at']));
        Schema::table('products_application', fn (Blueprint $t) => $t->dropColumn(['paid_at', 'payment_ref']));
        Schema::table('creator_profiles', fn (Blueprint $t) => $t->dropColumn(['payout_bank', 'payout_account_name', 'payout_account_number']));
    }
};

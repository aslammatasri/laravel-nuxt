<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brand_profiles', function (Blueprint $table) {
            // unverified | pending | verified | rejected
            $table->string('verification_status')->default('unverified')->after('is_verified');
            $table->text('verification_note')->nullable()->after('verification_status');
            $table->timestamp('submitted_at')->nullable()->after('verification_note');
            $table->timestamp('verified_at')->nullable()->after('submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('brand_profiles', function (Blueprint $table) {
            $table->dropColumn(['verification_status', 'verification_note', 'submitted_at', 'verified_at']);
        });
    }
};

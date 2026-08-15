<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->decimal('price', 10, 2)->nullable()->after('description');
            $table->string('commission_type')->nullable()->after('commission_rate');
            $table->timestamp('campaign_start')->nullable()->after('campaign_end');
            $table->integer('max_affiliates')->nullable()->after('campaign_start');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['description', 'price', 'commission_type', 'campaign_start', 'max_affiliates']);
        });
    }
};

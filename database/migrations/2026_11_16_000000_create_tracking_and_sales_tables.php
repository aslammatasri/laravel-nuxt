<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products_application', function (Blueprint $table) {
            $table->string('tracking_code', 16)->nullable()->unique();
        });

        Schema::create('link_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('products_application')->cascadeOnDelete();
            $table->string('ip_hash', 64);
            $table->string('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['application_id', 'ip_hash', 'created_at']);
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('products_application')->cascadeOnDelete();
            $table->string('order_ref', 60)->nullable();
            $table->decimal('amount', 10, 2);      // order value
            $table->decimal('commission', 10, 2);  // creator's cut, fixed at log time
            $table->string('status', 10)->default('approved'); // approved, paid, reversed
            $table->date('sold_at');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
        Schema::dropIfExists('link_clicks');
        Schema::table('products_application', function (Blueprint $table) {
            $table->dropColumn('tracking_code');
        });
    }
};

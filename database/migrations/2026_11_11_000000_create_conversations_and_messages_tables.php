<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            // One conversation per approved application
            $table->foreignId('application_id')->unique()->constrained('products_application')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['brand_id', 'last_message_at']);
            $table->index(['creator_id', 'last_message_at']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'id']);
        });

        // Backfill: conversations for applications that are already approved
        DB::table('products_application')
            ->join('products', 'products.id', '=', 'products_application.product_id')
            ->where('products_application.status', 'approved')
            ->whereNotNull('products.brand_id')
            ->select('products_application.id as application_id', 'products.id as product_id', 'products.brand_id', 'products_application.creator_id')
            ->orderBy('products_application.id')
            ->each(function ($row) {
                DB::table('conversations')->insert([
                    'application_id' => $row->application_id,
                    'product_id'     => $row->product_id,
                    'brand_id'       => $row->brand_id,
                    'creator_id'     => $row->creator_id,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};

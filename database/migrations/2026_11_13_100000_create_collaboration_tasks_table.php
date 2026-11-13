<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products_application', function (Blueprint $table) {
            $table->boolean('checklist_seeded')->default(false);
        });

        Schema::create('collaboration_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('products_application')->cascadeOnDelete();
            $table->string('title');
            $table->string('type', 20)->default('other'); // video, live, photo, story, post, sample, other
            $table->date('due_date')->nullable();
            $table->string('link')->nullable(); // posted content URL
            $table->timestamp('completed_at')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collaboration_tasks');
        Schema::table('products_application', function (Blueprint $table) {
            $table->dropColumn('checklist_seeded');
        });
    }
};

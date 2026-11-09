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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('deadline')->nullable();
            $table->decimal('grade_contribution', 5, 2);
            $table->enum('difficulty', ['Mudah', 'Sedang', 'Sulit']);
            $table->enum('status', ['Belum dikerjakan', 'Sedang dikerjakan', 'Selesai'])->default('Belum dikerjakan');
            $table->decimal('auto_score', 8, 4)->nullable();
            $table->enum('auto_priority', ['Tinggi', 'Sedang', 'Rendah'])->nullable();
            $table->enum('manual_priority', ['Tinggi', 'Sedang', 'Rendah'])->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};

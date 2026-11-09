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
        Schema::create('priority_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('urgency_weight', 6, 4)->default(0.5000);
            $table->decimal('contribution_weight', 6, 4)->default(0.3000);
            $table->decimal('difficulty_weight', 6, 4)->default(0.2000);
            $table->unsignedSmallInteger('horizon_days')->default(14);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('priority_settings');
    }
};

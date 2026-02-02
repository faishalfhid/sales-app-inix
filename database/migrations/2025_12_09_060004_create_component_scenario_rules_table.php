<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_scenario_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cost_component_id')->constrained()->onDelete('cascade');
            $table->foreignId('scenario_id')->constrained()->onDelete('cascade');
            $table->boolean('is_required')->default(false); // Y = true, n = false
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Pastikan tidak ada duplikat kombinasi component dan scenario
            $table->unique(['cost_component_id', 'scenario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_scenario_rules');
    }
};

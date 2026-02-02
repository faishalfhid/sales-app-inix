<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_cost_details', function (Blueprint $table) {
            $table->id();
            
            // PENTING: Gunakan foreignId() untuk konsistensi tipe data
            $table->foreignId('training_class_id')
                ->constrained('training_classes')
                ->onDelete('cascade');
                
            $table->foreignId('cost_component_id')
                ->constrained('cost_components')
                ->onDelete('restrict');
                
            $table->integer('period')->default(0);
            $table->integer('unit')->default(0);
            $table->integer('quantity')->default(0);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Index untuk performa
            $table->index('training_class_id');
            $table->index('cost_component_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_cost_details');
    }
};

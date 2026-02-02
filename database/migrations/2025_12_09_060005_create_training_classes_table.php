<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_classes', function (Blueprint $table) {
            $table->id(); // Ini menghasilkan UNSIGNED BIGINT
            $table->foreignId('scenario_id')->constrained('scenarios')->onDelete('restrict');
            $table->string('sales_name', 100)->nullable();
            $table->string('material', 200)->nullable();
            $table->string('customer', 200)->nullable();
            $table->integer('training_days')->default(0);
            $table->integer('admin_days')->default(0);
            $table->integer('participant_count')->default(0);
            $table->decimal('price_per_participant', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('price_after_discount', 15, 2)->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('real_revenue', 15, 2)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('net_profit', 15, 2)->default(0);
            $table->decimal('net_profit_margin', 8, 2)->default(0);
            $table->enum('status', ['draft', 'proposed', 'approved', 'running', 'completed', 'cancelled'])->default('draft');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Tambahkan index untuk performa
            $table->index('scenario_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_classes');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scenarios', function (Blueprint $table) {
            $table->id();
            $table->string('code', 100)->unique(); // offline_kantor, offline_inhouse_dibayar_client, dll
            $table->string('name', 200);
            $table->enum('type', ['offline', 'online']);
            $table->enum('location', ['kantor', 'inhouse']);
            $table->enum('payment_by', ['inix', 'client', 'mixed'])->nullable()->comment('Khusus untuk inhouse');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scenarios');
    }
};

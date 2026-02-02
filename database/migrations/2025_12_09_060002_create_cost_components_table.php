<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('name', 200); // Fee Instruktur, Biaya Proktor, dll
            $table->enum('nature', ['R', 'L'])->comment('R=Real Cost (Inix bayar), L=Pass Cost (Client bayar)');
            $table->enum('role', [
                'Instruktur', 
                'Peserta', 
                'Tim Inixindo', 
                'Tim dan Instruktur', 
                'Global', 
                'Operasional', 
                'Tambahan', 
                'Pajak'
            ]);
            $table->string('time_unit', 50)->nullable(); // Hari, Malam, Liter, Perjalanan, Kendaraan
            $table->string('quantity_unit', 50)->nullable(); // Pax, Transaksi
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_components');
    }
};

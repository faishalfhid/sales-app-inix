<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement("
        ALTER TABLE training_classes 
        MODIFY status ENUM(
            'draft',
            'proposed',
            'revision',
            'approved',
            'running',
            'completed',
            'cancelled'
        ) NOT NULL
    ");
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_classes_status', function (Blueprint $table) {
            //
        });
    }
};

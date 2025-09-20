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
        Schema::create('PROCESO', function (Blueprint $table) {
            $table->id()->name('proceso_id');
            $table->foreignId('ciclo_acreditacion_id')->constrained('CICLO_ACREDITACION', 'ciclo_acreditacion_id')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('PROCESO');
    }
};

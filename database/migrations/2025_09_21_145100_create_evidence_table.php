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
        Schema::create('EVIDENCIA', function (Blueprint $table) {
            $table->id()->name('evidencia_id');
            $table->foreignId('criterio_id')->constrained('CRITERIO', 'criterio_id');
            $table->foreignId('estado_evidencia_id')->constrained('ESTADO_EVIDENCIA', 'estado_evidencia_id');
            $table->string('descripcion', 80);
            $table->string('nomenclatura', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('EVIDENCIA');
    }
};

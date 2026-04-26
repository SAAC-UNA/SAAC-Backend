<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla pivot para vincular compromisos con evidencias del repositorio
        Schema::create('COMPROMISO_MEJORA_EVIDENCIA', function (Blueprint $table) {
            $table->foreignId('compromiso_mejora_id')
                ->constrained('COMPROMISO_MEJORA', 'compromiso_mejora_id')
                ->onDelete('cascade');

            $table->foreignId('evidencia_id')
                ->constrained('EVIDENCIA', 'evidencia_id')
                ->onDelete('cascade');

            $table->timestamps();

            // Clave primaria compuesta
            $table->primary(['compromiso_mejora_id', 'evidencia_id'], 'pk_compromiso_evidencia');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('COMPROMISO_MEJORA_EVIDENCIA');
    }
};

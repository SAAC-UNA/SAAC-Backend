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
            // Llave primaria
            $table->id('evidencia_id');

            // Llaves foráneas
            $table->unsignedBigInteger('criterio_id');
            $table->foreign('criterio_id')
                  ->references('criterio_id')
                  ->on('CRITERIO')
                  ->onDelete('cascade');

            $table->unsignedBigInteger('estado_evidencia_id');
            $table->foreign('estado_evidencia_id')
                  ->references('estado_evidencia_id')
                  ->on('ESTADO_EVIDENCIA')
                  ->onDelete('cascade');

            // Atributos
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

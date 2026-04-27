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
        // Tabla de carreras
        Schema::create('CARRERA', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('carrera_id');
            // Universidad a la que pertenece la carrera
            $table->unsignedBigInteger('universidad_id')->nullable();
            // Nombre de la carrera
            $table->string('nombre', 250);
            // Estado de la carrera
            $table->boolean('activo')->default(true);
            // Timestamps de creación y actualización
            $table->timestamps();

            // Foreign key
            $table->foreign('universidad_id', 'fk_ca_universidad')
                ->references('universidad_id')
                ->on('UNIVERSIDAD')
                ->onDelete('restrict');

            // Índices
            $table->index('universidad_id', 'idx_ca_universidad');
            $table->index('activo', 'idx_ca_activo');
            $table->index('nombre', 'idx_ca_nombre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla de carreras
        Schema::dropIfExists('CARRERA');
    }
};

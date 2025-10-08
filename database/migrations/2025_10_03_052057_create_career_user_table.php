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
        Schema::create('CARRERA_USUARIO', function (Blueprint $table) {
            //Clave primaria
            $table->id()->name('carrera_usuario_id');
            $table->foreignId('usuario_id')->constrained('USUARIO','usuario_id')->onDelete('cascade');
            $table->foreignId('carrera_id')->constrained('CARRERA','carrera_id')->onDelete('cascade');
            $table->unique(['carrera_id', 'usuario_id'], 'uq_carrera_usuario');// Restricción de unicidad para evitar duplicados
            $table->timestamps();
        });
    } 

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('CARRERA_USUARIO');
    }
};

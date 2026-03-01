<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('CARRERA_USUARIO', function (Blueprint $table) {
            //Clave primaria
            $table->id()->name('carrera_usuario_id');
            // Llave foránea hacia la tabla USUARIO
            $table->foreignId('usuario_id')->constrained('USUARIO','usuario_id')->onDelete('restrict');
            // Llave foránea hacia la tabla CARRERA
            $table->foreignId('carrera_id')->constrained('CARRERA','carrera_id')->onDelete('restrict');
            // Restricción de unicidad para evitar duplicados
            $table->unique(['carrera_id', 'usuario_id'], 'uq_carrera_usuario');
            // Campos de auditoría (created_at, updated_at)
            $table->timestamps();
            
            // Índices de performance
            $table->index('carrera_id', 'idx_cu_carrera_id');
            $table->index('usuario_id', 'idx_cu_usuario_id');
        });
    } 

    public function down(): void
    {
        Schema::dropIfExists('CARRERA_USUARIO');
    }
};

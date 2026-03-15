<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('JERARQUIA', function (Blueprint $table) {
            // Clave primaria
            $table->id()->name('jerarquia_id');
            
            // Autorreferencia (parent_id apunta a otro registro de JERARQUIA)
            $table->unsignedBigInteger('parent_id')->nullable();
            
            // Datos básicos
            $table->string('nombre', 100);
            $table->string('tipo', 30); // 'pauta', 'fuente', 'subdimension', etc.
            $table->string('nomenclatura', 20)->nullable();
            $table->text('descripcion')->nullable();
            
            // Ordenamiento y estado
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            
            // Timestamps
            $table->timestamps();
            
            // Foreign key autorreferencial
            $table->foreign('parent_id')
                  ->references('jerarquia_id')
                  ->on('JERARQUIA')
                  ->onDelete('restrict'); // No eliminar si tiene hijos
            
            // Índices para performance
            $table->index('parent_id', 'idx_jer_parent_id');
            $table->index('tipo', 'idx_jer_tipo');
            $table->index('activo', 'idx_jer_activo');
            $table->index(['parent_id', 'tipo'], 'idx_jer_parent_tipo');
            $table->index(['parent_id', 'orden'], 'idx_jer_parent_orden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('JERARQUIA');
    }
};

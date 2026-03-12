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
        Schema::create('ARCHIVO', function (Blueprint $table) {
            // Clave primaria
            $table->id('archivo_id');
            
            // Relaciones (LLAVES FORÁNEAS)
            $table->foreignId('evidencia_id')
                  ->constrained('EVIDENCIA', 'evidencia_id')
                  ->onDelete('restrict');
            
            $table->foreignId('usuario_id')
                  ->constrained('USUARIO', 'usuario_id')
                  ->onDelete('restrict');
            
            $table->foreignId('proceso_id')
                  ->constrained('PROCESO', 'proceso_id')
                  ->onDelete('restrict');
            
            // Fecha de subida
            $table->timestamp('fecha_subida');
            
            // TIPO: archivo físico o enlace externo
            $table->enum('tipo', ['archivo', 'enlace'])->default('archivo');
            
            // UBICACIÓN FÍSICA ÚNICA - Nombre UUID en TrueNAS (solo para tipo='archivo')
            $table->string('path', 512)->nullable();
            
            // URL externa (solo para tipo='enlace')
            $table->text('url')->nullable();
            
            // NUEVO: Nombre original del archivo (legible por humanos)
            $table->string('nombre_original', 255);

            // Metadatos del archivo físico (null para enlaces)
            $table->unsignedBigInteger('tamanio')->nullable();
            $table->string('tipo_mime', 255)->nullable();

            // NUEVO: Bandera de acceso público
            $table->boolean('is_publico')->default(false);
            
            // NUEVO: Token UUID para URL pública
            $table->string('token_publico', 36)->nullable()->unique();
            
            // NUEVO: Fecha de expiración del link público
            $table->timestamp('link_expira_en')->nullable();
            
            // Timestamps de creación y actualización
            $table->timestamps();
            
            // Índices para optimización
            $table->index('evidencia_id', 'idx_ar_evidencia_id');
            $table->index('usuario_id', 'idx_ar_usuario_id');
            $table->index('proceso_id', 'idx_ar_proceso_id');
            $table->index('token_publico', 'idx_ar_token_publico');
            $table->index('tipo', 'idx_ar_tipo');                              // WHERE tipo = 'archivo'/'enlace'
            $table->index(['evidencia_id', 'tipo'], 'idx_ar_evidencia_tipo');  // withCount por tipo en evidencia
            $table->index(['is_publico', 'link_expira_en'], 'idx_ar_publico_expira');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ARCHIVO');
    }
};

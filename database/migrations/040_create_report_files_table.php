<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla para registrar los archivos asociados a informes dentro de un proceso
        Schema::create('INFORME_ARCHIVO', function (Blueprint $table) {
            // Clave primaria
            $table->id('informe_archivo_id');
            // Claves foráneas
            $table->foreignId('proceso_id')->nullable()->constrained('PROCESO', 'proceso_id')->onDelete('restrict');
            // Clave foránea hacia el usuario que subió el archivo
            $table->foreignId('usuario_id')->constrained('USUARIO', 'usuario_id')->onDelete('restrict');
            // Clave foránea hacia el usuario que publicó el informe (puede ser null si no se ha publicado o si el publicador es el mismo que el uploader)
            $table->unsignedBigInteger('usuario_publicacion_id')->nullable()->comment('Usuario que publicó el informe');
            // Definición de la clave foránea hacia el usuario_publicacion_id
            $table->foreign('usuario_publicacion_id', 'ia_usuario_publicacion_fk')->references('usuario_id')->on('USUARIO')->onDelete('restrict');
            // Fecha y hora de subida del archivo
            $table->timestamp('fecha_subida');
            // Tipo de archivo (puede ser 'archivo' para archivos subidos o 'enlace' para URLs externas)
            $table->string('tipo', 255)->default('archivo');
            // Ubicación física del archivo (solo para tipo='archivo')
            $table->string('path', 512)->nullable();
            // URL del archivo (solo para tipo='enlace')
            $table->text('url')->nullable();
            // Nombre original del archivo
            $table->string('nombre_original', 255);
            // Tamaño del archivo en bytes
            $table->unsignedBigInteger('tamanio')->nullable();
            // Tipo MIME del archivo
            $table->string('tipo_mime', 255)->nullable();
            // Indicador de visibilidad pública
            $table->boolean('is_publico')->default(false);
            // Token para acceso público
            $table->string('token_publico', 36)->nullable()->unique();
            // Fecha de expiración del enlace público
            $table->timestamp('link_expira_en')->nullable();
            // Estado del archivo (publicado o despublicado)
            $table->enum('estado', ['publicado', 'despublicado'])->default('publicado')->comment('publicado=visible publicamente, despublicado=retirado');
            // Fecha de publicación del archivo (null si no se ha publicado o si el estado es despublicado)
            $table->timestamp('fecha_publicacion')->nullable();
            // Observaciones adicionales sobre el archivo
            $table->text('observaciones')->nullable();
            // Timestamps de creación y actualización
            $table->timestamps();

            // Indices
            $table->index('proceso_id', 'idx_ai_proceso_id');
            $table->index('usuario_id', 'idx_ai_usuario_id');
            $table->index('usuario_publicacion_id', 'idx_ia_usuario_pub');
            $table->index('token_publico', 'idx_ai_token_publico');
            $table->index('tipo', 'idx_ai_tipo');
            $table->index('estado', 'idx_ia_estado');
            $table->index(['proceso_id', 'tipo'], 'idx_ai_proceso_tipo');
            $table->index(['is_publico', 'link_expira_en'], 'idx_ai_publico_expira');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('INFORME_ARCHIVO');
    }
};

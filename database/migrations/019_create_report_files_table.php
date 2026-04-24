<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('INFORME_ARCHIVO', function (Blueprint $table) {
            $table->id('informe_archivo_id');

            $table->foreignId('proceso_id')
                ->nullable()
                ->constrained('PROCESO', 'proceso_id')
                ->onDelete('restrict');

            $table->foreignId('usuario_id')
                ->constrained('USUARIO', 'usuario_id')
                ->onDelete('restrict');

            $table->unsignedBigInteger('usuario_publicacion_id')
                ->nullable()
                ->comment('Usuario que publicó el informe');
            $table->foreign('usuario_publicacion_id', 'ia_usuario_publicacion_fk')
                ->references('usuario_id')
                ->on('USUARIO')
                ->onDelete('restrict');

            $table->timestamp('fecha_subida');
            $table->string('tipo', 255)->default('archivo');
            $table->string('path', 512)->nullable();
            $table->text('url')->nullable();
            $table->string('nombre_original', 255);
            $table->unsignedBigInteger('tamanio')->nullable();
            $table->string('tipo_mime', 255)->nullable();
            $table->boolean('is_publico')->default(false);
            $table->string('token_publico', 36)->nullable()->unique();
            $table->timestamp('link_expira_en')->nullable();

            $table->enum('estado', ['publicado', 'despublicado'])
                ->default('publicado')
                ->comment('publicado=visible publicamente, despublicado=retirado');

            $table->timestamp('fecha_publicacion')->nullable();
            $table->text('observaciones')->nullable();

            $table->timestamps();

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

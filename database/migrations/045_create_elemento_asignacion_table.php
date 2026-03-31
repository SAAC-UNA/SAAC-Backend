<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ELEMENTO_ASIGNACION', function (Blueprint $table) {
            $table->id('elemento_asignacion_id');

            $table->unsignedBigInteger('elemento_id');
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('proceso_id');
            $table->unsignedBigInteger('asignado_por')->nullable()
                  ->comment('usuario_id del encargado que realiza la asignación');

            $table->enum('estado', ['Pendiente', 'En Progreso', 'Completado', 'Vencido'])
                  ->default('Pendiente');
            $table->date('fecha_limite')->nullable();
            $table->text('comentario')->nullable();

            $table->timestamps();

            // Llaves foráneas
            $table->foreign('elemento_id', 'ea_elemento_id_foreign')
                  ->references('elemento_id')->on('ELEMENTO')->onDelete('cascade');

            $table->foreign('usuario_id', 'ea_usuario_id_foreign')
                  ->references('usuario_id')->on('USUARIO')->onDelete('cascade');

            $table->foreign('proceso_id', 'ea_proceso_id_foreign')
                  ->references('proceso_id')->on('PROCESO')->onDelete('cascade');

            $table->foreign('asignado_por', 'ea_asignado_por_foreign')
                  ->references('usuario_id')->on('USUARIO')->onDelete('set null');

            // Restricción: un usuario no puede estar asignado dos veces al mismo elemento en el mismo proceso
            $table->unique(['elemento_id', 'usuario_id', 'proceso_id'], 'ea_unique_elemento_usuario_proceso');

            // Índices
            $table->index('elemento_id', 'idx_ea_elemento_id');
            $table->index('usuario_id', 'idx_ea_usuario_id');
            $table->index('proceso_id', 'idx_ea_proceso_id');
            $table->index('estado', 'idx_ea_estado');
            $table->index('fecha_limite', 'idx_ea_fecha_limite');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ELEMENTO_ASIGNACION');
    }
};

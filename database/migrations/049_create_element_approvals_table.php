<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('APROBACION_ELEMENTO', function (Blueprint $table) {
            $table->id('aprobacion_elemento_id');

            // Elemento aprobado/rechazado (modelo flexible)
            $table->foreignId('elemento_id')
                ->constrained('ELEMENTO', 'elemento_id')
                ->onDelete('restrict');

            // Proceso al que pertenece
            $table->foreignId('proceso_id')
                ->constrained('PROCESO', 'proceso_id')
                ->onDelete('cascade');

            // Usuario que realiza la aprobación/rechazo
            $table->foreignId('usuario_id')
                ->constrained('USUARIO', 'usuario_id')
                ->onDelete('restrict');

            // Estado del bloque
            $table->enum('estado', ['aprobado', 'rechazado']);

            // Comentario opcional del revisor
            $table->string('comentario', 100)->nullable();

            $table->timestamps();

            // Índices de performance
            $table->index('elemento_id',  'idx_ae_elemento_id');
            $table->index('proceso_id',   'idx_ae_proceso_id');
            $table->index('usuario_id',   'idx_ae_usuario_id');
            $table->index('estado',       'idx_ae_estado');
            $table->index(['elemento_id', 'proceso_id'], 'idx_ae_elemento_proceso');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('APROBACION_ELEMENTO');
    }
};

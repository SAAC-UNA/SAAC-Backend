<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('SOLICITUD_AMPLIACION_ELEMENTO', function (Blueprint $table) {
            $table->id('solicitud_ampliacion_elemento_id');

            $table->unsignedBigInteger('elemento_asignacion_id');
            $table->foreign('elemento_asignacion_id', 'sae_elemento_asignacion_id_foreign')
                  ->references('elemento_asignacion_id')
                  ->on('ELEMENTO_ASIGNACION')
                  ->onDelete('restrict');

            $table->unsignedBigInteger('usuario_id');
            $table->foreign('usuario_id', 'sae_usuario_id_foreign')
                  ->references('usuario_id')
                  ->on('USUARIO')
                  ->onDelete('restrict');

            $table->string('motivo', 1000);
            $table->datetime('fecha_sugerida');
            $table->enum('estado', ['pendiente', 'aprobada', 'rechazada', 'cancelada'])
                  ->default('pendiente');
            $table->datetime('fecha_resolucion')->nullable();

            $table->unsignedBigInteger('usuario_resolutor_id')->nullable();
            $table->foreign('usuario_resolutor_id', 'sae_resolutor_id_foreign')
                  ->references('usuario_id')
                  ->on('USUARIO')
                  ->onDelete('restrict');

            $table->string('justificacion', 500)->nullable();

            $table->index('elemento_asignacion_id', 'idx_sae_elemento_asignacion_id');
            $table->index('usuario_id',             'idx_sae_usuario_id');
            $table->index('estado',                 'idx_sae_estado');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('SOLICITUD_AMPLIACION_ELEMENTO');
    }
};

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
            $table->unsignedBigInteger('elemento_id');
            $table->unsignedBigInteger('proceso_id');
            $table->unsignedBigInteger('usuario_id');
            $table->enum('estado', ['aprobado','rechazado','pendiente','incompleto'])->default('pendiente');
            $table->string('comentario', 100)->nullable();
            $table->timestamps();
            $table->foreign('elemento_id')->references('elemento_id')->on('ELEMENTO')->onDelete('restrict');
            $table->foreign('proceso_id')->references('proceso_id')->on('PROCESO')->onDelete('cascade');
            $table->foreign('usuario_id')->references('usuario_id')->on('USUARIO')->onDelete('restrict');
            $table->index('elemento_id','idx_ae_elemento_id');
            $table->index('proceso_id','idx_ae_proceso_id');
            $table->index('usuario_id','idx_ae_usuario_id');
            $table->index('estado','idx_ae_estado');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('APROBACION_ELEMENTO');
    }
};

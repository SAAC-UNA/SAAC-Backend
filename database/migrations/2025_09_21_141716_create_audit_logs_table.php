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
        Schema::create('BITACORA', function (Blueprint $table) {
            $table->id()->name('bitacora_id');
            $table->foreignId('tipo_accion_id')->constrained('TIPO_ACCION', 'tipo_accion_id')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('USUARIO', 'usuario_id')->onDelete('cascade');
            $table->timestamp('fecha_hora')->useCurrent();
            $table->text('detalle')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('BITACORA');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('CRITERIO', function (Blueprint $table) {
            $table->id()->name('criterio_id');

            // Claves foráneas (tipos compatibles con las PKs de las tablas destino)
            $table->unsignedBigInteger('componente_id');
            $table->unsignedBigInteger('comentario_id')->nullable();

            $table->string('descripcion', 300);
            $table->string('nomenclatura', 20);
            $table->timestamps();

            // FKs correctas (apuntan a las columnas personalizadas)
            $table->foreign('componente_id')
                  ->references('componente_id')->on('COMPONENTE')
                  ->cascadeOnDelete();

            $table->foreign('comentario_id')
                  ->references('comentario_id')->on('COMENTARIO')
                  ->nullOnDelete();

            // (Opcional pero recomendado) Evitar duplicar nomenclatura dentro del mismo componente
            $table->unique(['componente_id', 'nomenclatura'], 'uniq_comp_nomen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('CRITERIO');
    }
};

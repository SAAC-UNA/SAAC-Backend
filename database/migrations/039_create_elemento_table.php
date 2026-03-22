<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ELEMENTO', function (Blueprint $table) {
            $table->id('elemento_id');
            $table->unsignedBigInteger('padre_id')->nullable();
            $table->unsignedBigInteger('modelo_estructura_id')
                  ->comment('FK al modelo de estructura al que pertenece este elemento');
            $table->string('tipo', 30);
            $table->enum('categoria', ['A', 'B', 'C', 'D'])->nullable()
                  ->comment('Categoria de relevancia. Solo aplica para tipo=pauta');
            $table->string('nomenclatura', 20)->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->foreign('padre_id', 'elemento_padre_id_foreign')
                  ->references('elemento_id')->on('ELEMENTO')->onDelete('restrict');
            $table->foreign('modelo_estructura_id', 'elemento_modelo_estructura_id_foreign')
                  ->references('modelo_estructura_id')->on('MODELO_ESTRUCTURA')->onDelete('restrict');

            $table->index('padre_id', 'idx_elem_padre_id');
            $table->index('tipo', 'idx_elem_tipo');
            $table->index('activo', 'idx_elem_activo');
            $table->index('modelo_estructura_id', 'idx_elem_modelo');
            $table->index('categoria', 'idx_elem_categoria');
            $table->index(['padre_id', 'tipo'], 'idx_elem_padre_tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ELEMENTO');
    }
};

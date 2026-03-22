<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('MODELO_ESTRUCTURA', function (Blueprint $table) {
            $table->id('modelo_estructura_id');
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['tradicional', 'elemento_flexible']);
            $table->string('version', 20)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('tipo');
            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('MODELO_ESTRUCTURA');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('COMPONENTE', function (Blueprint $table) {
        $table->id()->name('componente_id'); // PK

        $table->unsignedBigInteger('dimension_id');
        $table->unsignedBigInteger('comentario_id')->nullable();

        $table->string('nombre', 80);
        $table->string('nomenclatura', 20);
        $table->timestamps();

        // Foreign Keys (solo si esas tablas existen)
        $table->foreign('dimension_id')
              ->references('dimension_id')->on('DIMENSION')
              ->cascadeOnDelete();

        $table->foreign('comentario_id')
              ->references('comentario_id')->on('COMENTARIO')
              ->nullOnDelete();
    });
}


    public function down(): void
    {
        Schema::dropIfExists('COMPONENTE');
    }
};
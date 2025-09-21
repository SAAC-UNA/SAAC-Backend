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
        Schema::create('COMPONENTE', function (Blueprint $table) {
            $table->id()->name('componente_id');
            $table->foreignId('dimension_id')->constrained('DIMENSION', 'dimension_id')->onDelete('cascade');
            $table->foreignId('comentario_id')->constrained('COMENTARIO', 'comentario_id')->onDelete('cascade');
            $table->string('nombre', 80);
            $table->string('nomenclatura', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('COMPONENTE');
    }
};

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
        Schema::create('CRITERIO', function (Blueprint $table) {
            $table->id()->name('criterio_id');
            $table->foreignId('componente_id')->constrained('COMPONENTE', 'componente_id');
            $table->foreignId('comentario_id')->constrained('COMENTARIO', 'comentario_id');
            $table->string('descripcion', 300);
            $table->string('nomenclatura', 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('CRITERIO');
    }
};

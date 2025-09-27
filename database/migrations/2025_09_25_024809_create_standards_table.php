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
        Schema::create('ESTANDAR', function (Blueprint $table) {
            $table->id('estandar_id');
            $table->unsignedBigInteger('criterio_id');
            $table->foreign('criterio_id')
            ->references('criterio_id')
            ->on('CRITERIO')
            ->cascadeOnDelete(); // Al borrar criterio, borra sus estándares
            
            $table->string('descripcion', 250);
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ESTANDAR');
    }
};

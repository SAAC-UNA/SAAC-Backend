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
            $table->id()->name('estandar_id');
            $table->foreignId('criterio_id')->constrained('CRITERIO')->name('criterio_id');
            $table->string('descripcion', 250)->name('descripcion');
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

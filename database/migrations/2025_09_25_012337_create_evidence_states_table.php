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
        Schema::create('ESTADO_EVIDENCIA', function (Blueprint $table) {
            $table->id('estado_evidencia_id');
            $table->string('nombre', 30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ESTADO_EVIDENCIA');
    }
};

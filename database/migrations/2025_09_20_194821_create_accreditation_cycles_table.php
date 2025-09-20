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
        Schema::create('CICLO_ACREDITACION', function (Blueprint $table) {
            $table->id()->name('ciclo_acreditacion_id');
            $table->foreignId('carrera_sede_id')->constrained('CARRERA_SEDE', 'carrera_sede_id')->onDelete('cascade');
            $table->string('nombre', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accreditation_cycles');
    }
};

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
        Schema::create('CARRERA_SEDE', function (Blueprint $table) {
            $table->id()->name('carrera_sede_id');
            $table->foreignId('carrera_id')->constrained('CARRERA', 'carrera_id')->onDelete('cascade');
            $table->foreignId('sede_id')->constrained('SEDE', 'sede_id')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('CARRERA_SEDE');
    }
};

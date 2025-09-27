<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('CARRERA', function (Blueprint $table) {
            $table->bigIncrements('carrera_id'); // PK correcta no con Name
            $table->unsignedBigInteger('facultad_id');
            $table->string('nombre', 250);
            $table->timestamps();

            // FK → FACULTAD
            $table->foreign('facultad_id')
                  ->references('facultad_id')->on('FACULTAD')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('CARRERA');
    }
};

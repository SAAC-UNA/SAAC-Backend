<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('FACULTAD', function (Blueprint $table) {
            $table->bigIncrements('facultad_id'); // En lugar de id()->name('facultad_id')

            // Claves foráneas (explicitas para asegurar columna y tabla correctas)
            $table->unsignedBigInteger('universidad_id');
            $table->unsignedBigInteger('sede_id');

            $table->string('nombre', 250);
            $table->timestamps();

            // FK -> UNIVERSIDAD(universidad_id)
            $table->foreign('universidad_id')
                  ->references('universidad_id')->on('UNIVERSIDAD')
                  ->onUpdate('cascade')->onDelete('cascade');

            // FK -> SEDE(sede_id)
            $table->foreign('sede_id')
                  ->references('sede_id')->on('SEDE')
                  ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('FACULTAD');
    }
};

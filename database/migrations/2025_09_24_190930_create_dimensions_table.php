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
        Schema::create('DIMENSION', function (Blueprint $table) {
            // PK correcta con nombre 'dimension_id'
            $table->id('dimension_id');

            // FK hacia COMENTARIO
            $table->foreignId('comentario_id')
                  ->constrained('COMENTARIO', 'comentario_id')
                  ->onDelete('cascade');

            $table->string('nombre', 100);
            $table->string('nomenclatura', 20);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('DIMENSION');
    }
};


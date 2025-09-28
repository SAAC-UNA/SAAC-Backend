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
        // Tabla de usuarios
        Schema::create('USUARIO', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id()->name('usuario_id');
            // Relación con rol (comentada, ajustar según integración con roles)
            #$table->foreignId('rol_id')->constrained('ROL')->onDelete('cascade');
            // Cédula única del usuario
            $table->string('cedula')->unique();
            // Nombre del usuario
            $table->string('nombre', 80);
            // Email del usuario
            $table->string('email');
            // Timestamps de creación y actualización
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Elimina la tabla de usuarios
        Schema::dropIfExists('USUARIO');
    }
};

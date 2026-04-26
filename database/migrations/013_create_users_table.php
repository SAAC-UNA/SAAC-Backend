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
            // Cédula única del usuario (uid de LDAP) Soporta: Nacional 9 dígitos, DIMEX 12 caracteres (puede tener letras)
            $table->string('cedula', 20)->unique();
            // Nombre completo (cn de LDAP)
            $table->string('nombre', 80);
            // Email único (mail de LDAP)
            $table->string('email', 255)->unique();
            // Password requerido por Authenticatable (siempre NULL, no se usa)
            $table->string('password', 255)->nullable();
            // Estado del usuario
            $table->enum('status', ['active', 'inactive'])->default('active');
            // Timestamps de creación y actualización
            $table->timestamps();
            // Índices
            $table->index('status', 'idx_us_status');
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

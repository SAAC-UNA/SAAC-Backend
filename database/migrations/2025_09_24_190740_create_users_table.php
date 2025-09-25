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
        Schema::create('USUARIO', function (Blueprint $table) {
            $table->id('usuario_id');             // PK bigint unsigned
            // Si más adelante usan roles con laravel-permission, aquí va la FK
            // $table->foreignId('rol_id')->constrained('ROL')->onDelete('cascade');

            $table->string('cedula')->unique();   // cédula única
            $table->string('nombre', 80);         // nombre con límite de 80
            $table->string('email');              // correo
            $table->timestamps();                 // created_at / updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('USUARIO');
    }
};
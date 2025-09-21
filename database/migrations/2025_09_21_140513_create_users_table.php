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
            $table->id('usuario_id');
            #CAMBIAR FOREIGNID SEGUN NOMBRE TABLA ROLES LARAVEL-PERMISSION
            #$table->foreignId('rol_id')->constrained('ROL')->onDelete('cascade'); 
            $table->string('cedula')->unique();
            $table->string('nombre', 80);
            $table->string('email');
            $table->timestamps();
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

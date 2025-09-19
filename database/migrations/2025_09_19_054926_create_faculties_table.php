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
        Schema::create('FACULTAD', function (Blueprint $table) {
            $table->id()->name('facultad_id');
            $table->foreignId('universidad_id')->constrained('UNIVERSIDAD', 'universidad_id')->onDelete('cascade');
            $table->foreignId('sede_id')->constrained('SEDE', 'sede_id')->onDelete('cascade');
            $table->string('nombre', 250);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('FACULTAD');
    }
};

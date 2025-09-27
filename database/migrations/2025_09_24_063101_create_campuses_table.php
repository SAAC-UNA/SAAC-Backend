<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('SEDE', function (Blueprint $table) {
            $table->bigIncrements('sede_id'); // PK real con nombre sede_id
            $table->foreignId('universidad_id')
                  ->constrained('UNIVERSIDAD', 'universidad_id')
                  ->onDelete('cascade');
            $table->string('nombre', 250);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('SEDE');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ELEMENTO_MEJORA_COMPROMISO', function (Blueprint $table) {
            $table->id('elemento_mejora_compromiso_id');
            $table->unsignedBigInteger('elemento_id');
            $table->unsignedBigInteger('compromiso_id');
            $table->timestamps();
            $table->foreign('elemento_id')->references('elemento_id')->on('ELEMENTO')->onDelete('cascade');
            $table->foreign('compromiso_id')->references('compromiso_id')->on('COMPROMISO_MEJORA')->onDelete('cascade');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('ELEMENTO_MEJORA_COMPROMISO');
    }
};

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
        Schema::create('UNIVERSIDAD', function (Blueprint $table) {
            $table->bigIncrements('universidad_id'); // ✅ en lugar de id()->name()
            $table->string('nombre', 250);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('UNIVERSIDAD');
    }
};

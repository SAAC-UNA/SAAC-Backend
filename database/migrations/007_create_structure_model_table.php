<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('MODELO_ESTRUCTURA', function (Blueprint $table) {
            $table->id('modelo_estructura_id');
            $table->string('nombre', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->enum('tipo', ['tradicional', 'elemento_flexible']);
            $table->string('version', 20)->nullable();
            $table->boolean('activo')->default(true);
            // Jerarquía de tipos de nodo (JSON) - migraciones post-creación
            $table->json('tipos_asignables')->nullable()
                ->comment('Tipos de nodo ELEMENTO que pueden recibir asignaciones y archivos en este modelo.');
            $table->json('tipos_jerarquia')->nullable()
                ->comment('Jerarquía de tipos de nodo ELEMENTO para modelos elemento_flexible.');
            $table->timestamps();

            $table->index('tipo');
            $table->index('activo');
        });

        // Dato del sistema: el modelo tradicional SINAES 2018 debe existir siempre.
        DB::table('MODELO_ESTRUCTURA')->insertOrIgnore([
            'nombre'      => 'SINAES 2018 - Estructura Tradicional',
            'tipo'        => 'tradicional',
            'descripcion' => 'Modelo clásico SINAES: Dimensión > Componente > Criterio > Evidencia.',
            'version'     => '2018',
            'activo'      => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('MODELO_ESTRUCTURA');
    }
};

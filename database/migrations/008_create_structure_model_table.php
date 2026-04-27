<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de modelos de estructura
        Schema::create('MODELO_ESTRUCTURA', function (Blueprint $table) {
            // Clave primaria BIGINT autoincremental
            $table->id('modelo_estructura_id');
            // Nombre del modelo (único)
            $table->string('nombre', 100)->unique();
            // Descripción del modelo
            $table->text('descripcion')->nullable();
            // Tipo de modelo
            $table->enum('tipo', ['tradicional', 'elemento_flexible']);
            // Versión del modelo
            $table->string('version', 20)->nullable();
            // Estado del modelo
            $table->boolean('activo')->default(true);
            $table->json('tipos_requieren_archivo')->nullable();
            // Jerarquía de tipos de nodo (JSON) - migraciones post-creación
            $table->json('tipos_asignables')->nullable()->comment('Tipos de nodo ELEMENTO que pueden recibir asignaciones y archivos en este modelo.');
            // Jerarquía de tipos de nodo (JSON) - migraciones post-creación
            $table->json('tipos_jerarquia')->nullable()->comment('Jerarquía de tipos de nodo ELEMENTO para modelos elemento_flexible.');
            // Timestamps de creación y actualización
            $table->timestamps();
            // Índices
            $table->index('tipo');
            $table->index('activo');
        });

        // Dato del sistema: el modelo tradicional SINAES 2018 debe existir siempre.
        DB::table('MODELO_ESTRUCTURA')->insertOrIgnore([
            'nombre' => 'SINAES 2018 - Estructura Tradicional',
            'tipo' => 'tradicional',
            'descripcion' => 'Modelo clásico SINAES: Dimensión > Componente > Criterio > Evidencia.',
            'version' => '2018',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('MODELO_ESTRUCTURA');
    }
};

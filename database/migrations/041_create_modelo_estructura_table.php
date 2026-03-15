<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tabla para definir modelos de estructura de evaluación
     * Permite vincular PROCESO con el modelo de jerarquía a usar
     */
    public function up(): void
    {
        // Crear tabla MODELO_ESTRUCTURA
        Schema::create('MODELO_ESTRUCTURA', function (Blueprint $table) {
            $table->id('modelo_estructura_id');
            $table->string('nombre', 100)->comment('Nombre descriptivo del modelo (ej: SINAES 2018)');
            $table->text('descripcion')->nullable()->comment('Descripción del modelo y sus características');
            $table->string('tipo', 30)->comment('tradicional | jerarquia_flexible');
            $table->string('version', 20)->nullable()->comment('Versión del modelo (ej: 2018, 2026)');
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->index('tipo');
            $table->index('activo');
        });

        // Insertar modelos predefinidos
        DB::table('MODELO_ESTRUCTURA')->insert([
            [
                'nombre' => 'SINAES 2018 - Estructura Tradicional',
                'descripcion' => 'Modelo de evaluación basado en la estructura clásica de SINAES: Dimensión > Componente > Criterio > Evidencia. Jerarquía rígida de 4 niveles.',
                'tipo' => 'tradicional',
                'version' => '2018',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'SINAES 2026 - Estructura Flexible con Pautas',
                'descripcion' => 'Nuevo modelo SINAES 2026 con estructura flexible basada en Pautas y Fuentes de Información. Permite jerarquías dinámicas y multinivel.',
                'tipo' => 'jerarquia_flexible',
                'version' => '2026',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Agregar campo a PROCESO
        Schema::table('PROCESO', function (Blueprint $table) {
            $table->unsignedBigInteger('modelo_estructura_id')
                ->default(1)
                ->after('tipo_proceso')
                ->comment('FK a MODELO_ESTRUCTURA - Define qué jerarquía usar');
            
            $table->foreign('modelo_estructura_id')
                ->references('modelo_estructura_id')
                ->on('MODELO_ESTRUCTURA')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        // Remover FK de PROCESO
        Schema::table('PROCESO', function (Blueprint $table) {
            $table->dropForeign(['modelo_estructura_id']);
            $table->dropColumn('modelo_estructura_id');
        });

        // Eliminar tabla
        Schema::dropIfExists('MODELO_ESTRUCTURA');
    }
};

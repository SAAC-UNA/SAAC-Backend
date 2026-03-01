<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Tabla de notificaciones internas del sistema
     * HU-018: Notificaciones automáticas
     */
    public function up(): void
    {
        Schema::create('NOTIFICACION', function (Blueprint $table) {
            $table->id('notificacion_id')->comment('ID de la notificación');
            
            // Usuario destinatario (FK a USUARIO)
            $table->unsignedBigInteger('usuario_id')->comment('Usuario que recibe la notificación');
            $table->foreign('usuario_id')->references('usuario_id')->on('USUARIO')->onDelete('cascade');
            
            // Tipo de evento que generó la notificación
            $table->enum('tipo_evento', [
                'asignacion_evidencia',
                'carga_archivo',
                'vencimiento_plazo',
                'devolucion_observacion',
                'aprobacion_criterio',
                'aprobacion_evidencia',
                'rechazo_evidencia',
                'solicitud_ampliacion',
                'respuesta_ampliacion',
                'comentario_nuevo',
                'actualizacion_sistema'
            ])->comment('Tipo de evento que generó la notificación');
            
            // Canal de notificación
            $table->enum('canal', ['interno', 'email', 'ambos'])->default('interno')
                ->comment('Canal por el que se envió: interno (sistema), email, o ambos');
            
            // Título y mensaje
            $table->string('titulo', 200)->comment('Título breve de la notificación');
            $table->text('mensaje')->comment('Mensaje descriptivo completo');
            
            // Estado de lectura
            $table->boolean('leida')->default(false)->comment('Si la notificación fue leída');
            $table->timestamp('fecha_lectura')->nullable()->comment('Cuándo se marcó como leída');
            
            // Referencia a entidad relacionada (polimórfico) - NULLABLE
            $table->nullableMorphs('relacionado'); // Crea relacionado_type y relacionado_id
            
            // URL o ruta del sistema para acceso directo
            $table->string('enlace', 500)->nullable()->comment('Enlace directo al recurso');
            
            // Estado de entrega de email (si aplica)
            $table->enum('estado_email', ['pendiente', 'enviado', 'fallido', 'no_aplica'])->default('no_aplica')
                ->comment('Estado del envío por email');
            $table->text('detalle_error')->nullable()->comment('Detalle del error si falló el envío');
            
            // Metadatos adicionales en JSON
            $table->json('metadatos')->nullable()->comment('Datos adicionales en formato JSON');
            
            // Timestamps
            $table->timestamp('created_at')->useCurrent()->comment('Fecha de creación');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->comment('Fecha de actualización');
            
            // Índices para consultas frecuentes
            $table->index(['usuario_id', 'leida'], 'idx_usuario_leida');
            $table->index(['usuario_id', 'created_at'], 'idx_usuario_fecha');
            $table->index('tipo_evento', 'idx_tipo_evento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('NOTIFICACION');
    }
};

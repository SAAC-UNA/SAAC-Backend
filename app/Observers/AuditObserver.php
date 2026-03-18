<?php

namespace App\Observers;

use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;

/**
 * Observer genérico que registra eventos CRUD en la bitácora del sistema.
 *
 * Se configura con el nombre del módulo y el atributo que representa
 * el nombre del modelo. Se instancia una vez por modelo en AppServiceProvider.
 *
 * Satisface DRY: elimina la repetición de AuditLogService::log() en los
 * controllers de estructura (Career, Campus, Dimension, Component, etc.).
 */
class AuditObserver
{
    public function __construct(
        private readonly string $module,
        private readonly string $nameAttr = 'nombre'
    ) {}

    public function created(Model $model): void
    {
        $name = $model->{$this->nameAttr} ?? class_basename($model);
        AuditLogService::log(
            'crear',
            "Se creó {$this->module}: \"{$name}\" (ID: {$model->getKey()}).",
            $this->module
        );
    }

    public function updated(Model $model): void
    {
        // Ignorar si el único cambio es updated_at
        $changes = array_diff_key($model->getChanges(), ['updated_at' => true]);
        if (empty($changes)) {
            return;
        }

        $parts = [];
        foreach ($changes as $field => $newValue) {
            $oldValue = $model->getOriginal($field);
            $parts[]  = "{$field}: \"{$oldValue}\" → \"{$newValue}\"";
        }

        AuditLogService::log(
            'editar',
            "Se actualizó {$this->module} ID {$model->getKey()}: " . implode('; ', $parts) . '.',
            $this->module
        );
    }

    public function deleted(Model $model): void
    {
        $name = $model->{$this->nameAttr} ?? class_basename($model);
        AuditLogService::log(
            'eliminar',
            "Se eliminó {$this->module}: \"{$name}\" (ID: {$model->getKey()}).",
            $this->module
        );
    }
}

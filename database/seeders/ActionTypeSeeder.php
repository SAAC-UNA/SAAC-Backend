<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;// Importa la clase base de laravel seeder
use App\Models\ActionType;// Importa el modelo para poder usar el ACTIONTYPE

class ActionTypeSeeder extends Seeder
{
    /**
     * Hereda de la clase base de lavarel que permite ejecutar seeders
     * Puebla la tabla TIPO_ACCION con los tipos de acciones del sistema.
     * Estas son las acciones que se registrarán en la bitácora.
     */
    public function run(): void
    {
        //variable que contiene los tipos de acciones"array(lista)"
        $actions = [
            // Acciones CRUD básicas
            ['descripcion' => 'crear'],
            ['descripcion' => 'editar'],
            ['descripcion' => 'eliminar'],
            ['descripcion' => 'consultar'],
            
            // Acciones de autenticación
            ['descripcion' => 'login'],
            ['descripcion' => 'logout'],
            ['descripcion' => 'login_fallido'],
            
            // Acciones de gestión de usuarios
            ['descripcion' => 'activar'],
            ['descripcion' => 'desactivar'],
            ['descripcion' => 'asignar_rol'],
            ['descripcion' => 'asignar_permisos'],
            
            // Otras acciones del sistema
            ['descripcion' => 'exportar'],
            ['descripcion' => 'asignar'],
            
            // Acciones de notificaciones (HU-018)
            ['descripcion' => 'notificar'],
            ['descripcion' => 'notificar_fallido'],
        ];
        // Recorre cada acción y la crea en la base de datos
        foreach ($actions as $action) {
            ActionType::create($action);
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Requests\RoleRequest;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

/**
 * Controlador para la gestión de roles y sus permisos.
 */
class RoleController extends Controller
{
    private RoleService $rolServicio;

    /**
     * Constructor del controlador.
     *
     * @param RoleService $rolServicio Servicio para la gestión de roles.
     */
    public function __construct(RoleService $rolServicio)
    {
        $this->rolServicio = $rolServicio;
    }

    /**
     * Listar todos los roles con sus permisos asociados.
     *
     * @return JsonResponse JSON con la lista de roles y sus permisos.
     */
    public function listarRoles(): JsonResponse
    {
        $roles = Role::with('permissions:id,name')->get()
            ->map(function ($rol) {
                return [
                    'id'          => $rol->id,
                    'name'        => $rol->name,
                    'description' => $rol->description,
                    'permissions' => $rol->permissions->pluck('name'), // solo nombres
                ];
            });

        return response()->json(['datos' => $roles], 200);
    }

    /**
     * Crear un nuevo rol con sus permisos.
     *
     * @param RoleRequest $request Datos validados para crear el rol.
     * @return JsonResponse JSON con el rol creado y sus permisos.
     */
    public function crearRol(RoleRequest $request): JsonResponse
    {
        $rol = $this->rolServicio->crearRol($request->validated());

        return response()->json([
            'mensaje' => 'Rol creado con éxito',
            'datos'   => [
                'id'          => $rol->id,
                'name'        => $rol->name,
                'description' => $rol->description,
                'permissions' => $rol->permissions->pluck('name'),
            ],
        ], 201);
    }

    /**
     * Mostrar un rol específico.
     *
     * @param int $id Identificador del rol.
     * @return JsonResponse JSON con los datos del rol o un error si no existe.
     */
    public function mostrarRol(int $id): JsonResponse
    {
        $rol = Role::with('permissions')->find($id);

        if (!$rol) {
            return response()->json(['mensajeError' => 'Rol no encontrado'], 404);
        }

        return response()->json(['datos' => $rol], 200);
    }

    /**
     * Actualizar un rol existente.
     *
     * @param RoleRequest $request Datos validados para actualizar el rol.
     * @param int $id Identificador del rol a actualizar.
     * @return JsonResponse JSON con el rol actualizado o un error si no existe.
     */
    public function actualizarRol(RoleRequest $request, int $id): JsonResponse
    {
        // Buscar el rol
        $rol = $this->rolServicio->obtenerRol($id);

        if (!$rol) {
            return response()->json(['mensajeError' => 'Rol no encontrado'], 404);
        }

        // Guardar estado original antes de actualizar
        $original = [
            'name'        => $rol->name,
            'description' => $rol->description,
            'permissions' => $rol->permissions->pluck('name')->sort()->values()->toArray(),
        ];

        // Ejecutar actualización (devuelve un Role)
        $rolActualizado = $this->rolServicio->actualizarRol($rol, $request->validated());

        // Guardar estado nuevo después de actualizar
        $nuevo = [
            'name'        => $rolActualizado->name,
            'description' => $rolActualizado->description,
            'permissions' => $rolActualizado->permissions->pluck('name')->sort()->values()->toArray(),
        ];

        // Determinar si hubo cambios
        if ($original == $nuevo) {
            return response()->json([
                'mensaje' => 'No se realizaron cambios en el rol',
                'datos'   => [
                    'id'          => $rolActualizado->id,
                    'name'        => $rolActualizado->name,
                    'description' => $rolActualizado->description,
                    'permissions' => $rolActualizado->permissions->pluck('name'),
                ],
            ], 200);
        }

        // Si hubo cambios
        return response()->json([
            'mensaje' => 'Rol actualizado con éxito',
            'datos'   => [
                'id'          => $rolActualizado->id,
                'name'        => $rolActualizado->name,
                'description' => $rolActualizado->description,
                'permissions' => $rolActualizado->permissions->pluck('name'),
            ],
        ], 200);
    }

    /**
     * Listar todos los permisos disponibles.
     *
     * @return JsonResponse
     */
    public function listarPermisos(): JsonResponse
    {
        $permisos = $this->rolServicio->listarPermisos();

        return response()->json(['datos' => $permisos], 200);
    }

     /**
      * Eliminar un rol existente.
      *
      * @param int $id Identificador del rol a eliminar.
      * @return JsonResponse
      */
      // En tu archivo App\Http\Controllers\RoleController.php

    public function eliminarRol(int $id): JsonResponse
    {
      // Llamamos al método del servicio para eliminar el rol
        $resultado = $this->rolServicio->eliminarRol($id);

      // El método del servicio devuelve un booleano, lo revisamos
        if (!$resultado) {
            return response()->json(['mensajeError' => 'Rol no encontrado'], 404);
        }

        return response()->json(['mensaje' => 'Rol eliminado con éxito'], 200);
    }


}

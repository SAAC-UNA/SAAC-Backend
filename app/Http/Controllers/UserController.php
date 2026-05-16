<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

//use App\Models\Role; //modelo que extiende SpatieRole
use App\Services\UserAdminService;
use App\Services\LdapService;
use App\Services\AuditLogService;
use App\Http\Requests\AssignRoleRequest;
use App\Http\Requests\AssignPermissionsRequest;
use App\Http\Requests\AssignCareersRequest;





class UserController extends Controller
{
    public function __construct(
        private UserAdminService $userAdmin,
        private LdapService $ldapService
    ) {}
   

    /**
     * Listar todos los usuarios con sus roles, permisos directos
     * y permisos efectivos (roles + directos).
     */
    public function index()
    {
        // Cargamos roles, permisos directos y permisos de cada rol
        // para que getAllPermissions() en UserResource no dispare lazy loads por usuario
        $users = User::with(['roles', 'permissions', 'roles.permissions', 'careers.career'])
            ->orderBy('created_at', 'desc')
            ->get();

        return UserResource::collection($users);
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        Gate::authorize('usuarios.create');

        $cedula = (string) $request->string('cedula')->trim();
        $roleName = (string) $request->string('role')->trim();

        if (User::where('cedula', $cedula)->exists()) {
            return response()->json([
                'message' => 'El usuario ya existe en SAAC',
            ], 409);
        }

        $ldapData = $this->ldapService->getUserDataFromLdap($cedula);

        if (!$ldapData) {
            return response()->json([
                'message' => 'No se encontró un usuario en LDAP con la cédula indicada',
            ], 404);
        }

        if (User::where('cedula', $ldapData['cedula'])->exists()) {
            return response()->json([
                'message' => 'El usuario ya existe en SAAC',
            ], 409);
        }

        if (User::where('email', $ldapData['email'])->exists()) {
            return response()->json([
                'message' => 'Ya existe un usuario en SAAC con el correo obtenido desde LDAP',
            ], 409);
        }

        $user = DB::transaction(function () use ($ldapData, $roleName) {
            $createdUser = User::create([
                'cedula' => $ldapData['cedula'],
                'nombre' => $ldapData['nombre'],
                'email'  => $ldapData['email'],
                'status' => User::STATUS_ACTIVE,
            ]);

            $createdUser->assignRole($roleName);

            return $createdUser->fresh(['roles', 'permissions', 'roles.permissions', 'careers.career']);
        });

        AuditLogService::log(
            'crear',
            "Usuario \"{$user->nombre}\" creado desde LDAP con rol '{$roleName}'",
            'Usuarios'
        );

        return response()->json([
            'message' => 'Usuario creado desde LDAP correctamente',
            'data' => new UserResource($user),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
    public function activate(User $user): JsonResponse
    {
        // Verificar autorización - solo usuarios con permiso usuarios.edit
        Gate::authorize('usuarios.edit');
        
        if ($user->status === User::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'El usuario ya estaba activo',
                'user_id' => $user->usuario_id,
            ], 409);
        }

        $this->userAdmin->activate($user);
        
        // Registrar en bitácora
        AuditLogService::log(
            'activar',
            "Usuario \"{$user->nombre}\" activado.",
            'Usuarios'
        );
        
        // TODO Sprint 3: event(new UserAdminActionPerformed(... 'activate' ...));
        return response()->json(['message' => 'Usuario activado'], 200);
    }

    public function deactivate(User $user): JsonResponse
    {
        // Verificar autorización - solo usuarios con permiso usuarios.edit
        Gate::authorize('usuarios.edit');
        
        if ($user->status === User::STATUS_INACTIVE) {
            return response()->json([
                'message' => 'El usuario ya estaba inactivo',
                'user_id' => $user->usuario_id,
            ], 409);
        }

        $this->userAdmin->deactivate($user);
        
        // Registrar en bitácora
        AuditLogService::log(
            'desactivar',
            "Usuario \"{$user->nombre}\" desactivado.",
            'Usuarios'
        );
        
        // TODO Sprint 3: event(new UserAdminActionPerformed(... 'deactivate' ...));
        return response()->json(['message' => 'Usuario desactivado'], 200);
    }
    public function assignRole(AssignRoleRequest $request, User $user)
    {
        // Verificar autorización - solo usuarios con permiso usuarios.edit
        Gate::authorize('usuarios.edit');
        
        //trim() limpia la cadena para asegurar que el valor del rol sea exacto y no contenga espacios extra antes o después.
        $roleName = $request->string('role')->trim();// Ya esta validado
        //delegar la asignación de rol al servicio
        $this->userAdmin->assignRole($user, $roleName);
        
        // Registrar en bitácora
        AuditLogService::log(
            'asignar_rol',
            "Rol '{$roleName}' asignado a \"{$user->nombre}\".",
            'Usuarios'
        );
    
        // TODO Sprint 3: event(new UserAdminActionPerformed(... 'assign_role' ...));

        return response()->json([
            'message' => 'Rol asignado correctamente',
            'user_id' => $user->usuario_id,
            'role'    => $roleName,
        ], 200);
     
    }
    public function assignPermissions(AssignPermissionsRequest $request, User $user): \Illuminate\Http\JsonResponse
    {
        // Verificar autorización - solo usuarios con permiso usuarios.edit
        Gate::authorize('usuarios.edit');
        
        $modules = $request->input('modules', []);
        $this->userAdmin->setModulePermissions($user, $modules);
        
        // Registrar en bitácora
        $assignedPermissions = $user->getDirectPermissions()->pluck('name')->values()->toArray();
        AuditLogService::log(
            'asignar_permisos',
            "Permisos actualizados para \"{$user->nombre}\". Permisos: " . implode(', ', $assignedPermissions),
            'Usuarios'
        );
        
        // TODO Sprint 3: event(new UserAdminActionPerformed(... 'assign_permissions' ...));

        return response()->json([
            'message' => 'Permisos actualizados correctamente',
            'user_id' => $user->usuario_id,
            'granted' => $assignedPermissions, 
        ], 200);
    }

    public function assignCareers(AssignCareersRequest $request, User $user): JsonResponse
    {
        $actor = $request->user();
        if (!$actor) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        $careers = $request->input('careers', []);
        $updatedUser = $this->userAdmin->setCareers($actor, $user, $careers);

        AuditLogService::log(
            'asignar_carreras',
            "Carreras actualizadas para \"{$updatedUser->nombre}\".",
            'Usuarios'
        );

        return response()->json([
            'message' => 'Carreras asignadas correctamente.',
            'user_id' => $updatedUser->usuario_id,
            'careers' => $updatedUser->careers->map(fn ($careerCampus) => [
                'carrera_sede_id' => $careerCampus->carrera_sede_id,
                'carrera_id'      => $careerCampus->carrera_id,
                'nombre'          => $careerCampus->career?->nombre,
            ])->values(),
        ], 200);
    }
    
    // pluck('name') significa que extrae únicamente el valor del campo name de cada permiso que tiene el usuario.
}

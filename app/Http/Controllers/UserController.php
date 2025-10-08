<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\JsonResponse;
//use Illuminate\Support\Facades\Gate; 3 sprint

//use App\Models\Role; //modelo que extiende SpatieRole
use App\Services\UserAdminService;
use App\Http\Requests\AssignRoleRequest;
use App\Http\Requests\AssignPermissionsRequest;





class UserController extends Controller
{
    public function __construct(private UserAdminService $userAdmin) {}
    /**
     * Display a listing of the resource.
     */
    /**
 * Listar todos los usuarios con sus roles y estado
    */
    public function index()
    {
        $users = User::with(['roles', 'permissions'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($users, 200); // Retorna JSON
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
    public function store(StoreUserRequest $request)
    {
        //
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
        
        if ($user->status === User::STATUS_ACTIVE) {
            return response()->json([
                'message' => 'El usuario ya estaba activo',
                'user_id' => $user->usuario_id,
            ], 409);
        }

        $this->userAdmin->activate($user);
        // TODO Sprint 3: event(new UserAdminActionPerformed(... 'activate' ...));
        return response()->json(['message' => 'Usuario activado'], 200);
    }

    public function deactivate(User $user): JsonResponse
    {
        
        if ($user->status === User::STATUS_INACTIVE) {
            return response()->json([
                'message' => 'El usuario ya estaba inactivo',
                'user_id' => $user->usuario_id,
            ], 409);
        }

        $this->userAdmin->deactivate($user);
        // TODO Sprint 3: event(new UserAdminActionPerformed(... 'deactivate' ...));
        return response()->json(['message' => 'Usuario desactivado'], 200);
    }
    public function assignRole(AssignRoleRequest $request, User $user)
    {
        // Verificar autorización (solo usuarios con permiso pueden asignar roles)
        // DEV: simula usuario que realiza la acción (quien "administra")
    //$acting = User::where('email', 'admin@saacuna.local')->first(); // o User::find(1/4)
       // Gate::forUser($acting)->authorize('usuarios.edit'); // lanza 403 si no tiene permiso
        // TODO Sprint 3: quitar forUser y usar usuario autenticado (LDAP
        // o: if (\Gate::denies('usuarios.edit')) abort(403, 'No tiene permiso para editar usuarios');
        //trim() limpia la cadena para asegurar que el valor del rol sea exacto y no contenga espacios extra antes o después.
        $roleName = $request->string('role')->trim();// Ya esta validado
        //delegar la asignación de rol al servicio
        $this->userAdmin->assignRole($user, $roleName);
    
        // TODO Sprint 3: event(new UserAdminActionPerformed(... 'assign_role' ...));

        return response()->json([
            'message' => 'Rol asignado correctamente',
            'user_id' => $user->usuario_id,
            'role'    => $roleName,
        ], 200);
     
    }
    public function assignPermissions(AssignPermissionsRequest $request, User $user): \Illuminate\Http\JsonResponse
    {
        $modules = $request->input('modules', []);
        $this->userAdmin->setModulePermissions($user, $modules);
        // TODO Sprint 3: event(new UserAdminActionPerformed(... 'assign_permissions' ...));

        return response()->json([
            'message' => 'Permisos actualizados correctamente',
            'user_id' => $user->usuario_id,
            'granted' => $user->getDirectPermissions()->pluck('name')->values(), 
        ], 200);
    }
    
    // pluck('name') significa que extrae únicamente el valor del campo name de cada permiso que tiene el usuario.
}

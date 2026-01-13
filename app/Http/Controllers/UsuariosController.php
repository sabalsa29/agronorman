<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsuariosRequest;
use App\Http\Requests\UpdateUsuariosRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\UserMenuPermission;
use App\Models\Usuarios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UsuariosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $usuarios = User::where('cliente_id', $request->id)->get();

        // Obtener el nombre del cliente de forma segura
        $clienteNombre = "";
        if ($usuarios->isNotEmpty() && $usuarios->first()->cliente) {
            $clienteNombre = $usuarios->first()->cliente->nombre ?? "";
        } else {
            // Si no hay usuarios, intentar obtener el cliente directamente
            $cliente = \App\Models\Clientes::find($request->id);
            $clienteNombre = $cliente->nombre ?? "";
        }

        return view('clientes.usuarios.index', [
            "section_name" => "Lista de Usuarios" . ($clienteNombre ? " del usuario " . $clienteNombre : ""),
            "section_description" => "Usuarios del sistema",
            "list" => $usuarios,
            "cliente_id" => $request->id,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $rolesPermitidos = $this->getRolesPermitidos();

        // Obtener grupos disponibles según el usuario autenticado Y el cliente
        $user = Auth::user();
        $clienteId = $request->id;

        // Si el usuario es super admin, puede ver todos los grupos
        // Si no, solo los grupos asignados al cliente
        if ($user->isSuperAdmin()) {
            $gruposDisponibles = \App\Models\Grupos::with('grupoPadre')
                ->forUser($user)
                ->get()
                ->map(function ($grupo) {
                    return [
                        'id' => $grupo->id,
                        'nombre' => $grupo->ruta_completa,
                    ];
                });
        } else {
            // Obtener grupos asignados al cliente
            $cliente = \App\Models\Clientes::find($clienteId);
            if ($cliente) {
                $gruposDelCliente = $cliente->grupos->pluck('id')->toArray();

                // Obtener todos los grupos descendientes de los grupos del cliente
                $todosLosGruposPermitidos = collect();
                foreach ($cliente->grupos as $grupoPadre) {
                    $descendientes = collect($grupoPadre->obtenerDescendientes());
                    $todosLosGruposPermitidos = $todosLosGruposPermitidos->merge($descendientes);
                }

                $gruposDisponibles = \App\Models\Grupos::with('grupoPadre')
                    ->whereIn('id', $todosLosGruposPermitidos->unique()->toArray())
                    ->get()
                    ->map(function ($grupo) {
                        return [
                            'id' => $grupo->id,
                            'nombre' => $grupo->ruta_completa,
                        ];
                    });
            } else {
                $gruposDisponibles = collect();
            }
        }

        return view('clientes.usuarios.create', [
            "section_name" => "Crear Usuario",
            "section_description" => "Crear un nuevo usuario",
            "cliente_id" => $request->id,
            "roles" => $rolesPermitidos,
            "gruposDisponibles" => $gruposDisponibles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUsuariosRequest $request)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Usuario no autenticado.']);
        }

        // Validar que el role_id sea permitido
        $rolesPermitidos = $this->getRolesPermitidos()->pluck('id')->toArray();
        if (!in_array($request->role_id, $rolesPermitidos)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['role_id' => 'No tiene permisos para asignar este rol.']);
        }

        // Validar que solo Super Admin pueda crear Super Admin
        if ($request->role_id == 1 && !$user->isSuperAdmin()) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['role_id' => 'Solo el Super Administrador puede crear Super Administradores.']);
        }

        // Guardar el cliente_id original para la redirección (antes de establecerlo en null para Super Admin)
        $clienteIdParaRedireccion = $request->cliente_id;

        // Si es Super Admin, cliente_id debe ser null
        if ($request->role_id == 1) {
            $request->merge(['cliente_id' => null]);
        }

        // Validar que el grupo asignado pertenezca al cliente (si no es super admin)
        if ($request->grupo_id && !$user->isSuperAdmin()) {
            $cliente = \App\Models\Clientes::find($request->cliente_id);
            if ($cliente) {
                // Obtener todos los grupos descendientes de los grupos del cliente
                $todosLosGruposPermitidos = collect();
                foreach ($cliente->grupos as $grupoPadre) {
                    $descendientes = collect($grupoPadre->obtenerDescendientes());
                    $todosLosGruposPermitidos = $todosLosGruposPermitidos->merge($descendientes);
                }

                if (!$todosLosGruposPermitidos->contains($request->grupo_id)) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['grupo_id' => 'El grupo seleccionado no pertenece a este cliente.']);
                }
            }
        }

        $usuario = new User();
        $usuario->cliente_id = $request->cliente_id;
        $usuario->nombre = $request->nombre;
        $usuario->email = $request->email;
        $usuario->password = bcrypt($request->password);
        $usuario->role_id = $request->role_id;
        $usuario->grupo_id = $request->grupo_id ?: null;
        $usuario->save();

        // Si es Super Admin, redirigir a la lista de clientes
        // Si no, redirigir a la lista de usuarios del cliente
        if ($request->role_id == 1) {
            return redirect()->route('clientes.index')->with('success', 'Super Administrador creado correctamente');
        }

        return redirect()->route('usuarios.index', ['id' => $clienteIdParaRedireccion])->with('success', 'Usuario creado correctamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Usuarios $usuarios)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id, User $usuario)
    {
        $rolesPermitidos = $this->getRolesPermitidos();

        // Obtener grupos disponibles según el usuario autenticado Y el cliente
        $user = Auth::user();
        $clienteId = $id;

        // Si el usuario es super admin, puede ver todos los grupos
        // Si no, solo los grupos asignados al cliente
        if ($user->isSuperAdmin()) {
            $gruposDisponibles = \App\Models\Grupos::with('grupoPadre')
                ->forUser($user)
                ->get()
                ->map(function ($grupo) {
                    return [
                        'id' => $grupo->id,
                        'nombre' => $grupo->ruta_completa,
                    ];
                });
        } else {
            // Obtener grupos asignados al cliente
            $cliente = \App\Models\Clientes::find($clienteId);
            if ($cliente) {
                $gruposDelCliente = $cliente->grupos->pluck('id')->toArray();

                // Obtener todos los grupos descendientes de los grupos del cliente
                $todosLosGruposPermitidos = collect();
                foreach ($cliente->grupos as $grupoPadre) {
                    $descendientes = collect($grupoPadre->obtenerDescendientes());
                    $todosLosGruposPermitidos = $todosLosGruposPermitidos->merge($descendientes);
                }

                $gruposDisponibles = \App\Models\Grupos::with('grupoPadre')
                    ->whereIn('id', $todosLosGruposPermitidos->unique()->toArray())
                    ->get()
                    ->map(function ($grupo) {
                        return [
                            'id' => $grupo->id,
                            'nombre' => $grupo->ruta_completa,
                        ];
                    });
            } else {
                $gruposDisponibles = collect();
            }
        }

        return view('clientes.usuarios.edit', [
            "section_name" => "Editar Usuario",
            "section_description" => "Editar un usuario existente",
            "usuario" => $usuario,
            "cliente_id" => $id,
            "roles" => $rolesPermitidos,
            "gruposDisponibles" => $gruposDisponibles,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUsuariosRequest $request, $id, User $usuario)
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Usuario no autenticado.']);
        }

        // Validar que el role_id sea permitido
        $rolesPermitidos = $this->getRolesPermitidos()->pluck('id')->toArray();
        if ($request->has('role_id') && !in_array($request->role_id, $rolesPermitidos)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['role_id' => 'No tiene permisos para asignar este rol.']);
        }

        // Validar que solo Super Admin pueda asignar Super Admin
        if ($request->has('role_id') && $request->role_id == 1 && !$user->isSuperAdmin()) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['role_id' => 'Solo el Super Administrador puede asignar el rol de Super Administrador.']);
        }

        // Si se cambia a Super Admin, cliente_id debe ser null
        if ($request->has('role_id') && $request->role_id == 1) {
            $usuario->cliente_id = null;
        }

        // Validar que el grupo asignado pertenezca al cliente (si no es super admin)
        if ($request->has('grupo_id') && $request->grupo_id && !$user->isSuperAdmin()) {
            $cliente = \App\Models\Clientes::find($id);
            if ($cliente) {
                $gruposDelCliente = $cliente->grupos->pluck('id')->toArray();

                // Obtener todos los grupos descendientes de los grupos del cliente
                $todosLosGruposPermitidos = collect();
                foreach ($cliente->grupos as $grupoPadre) {
                    $descendientes = collect($grupoPadre->obtenerDescendientes());
                    $todosLosGruposPermitidos = $todosLosGruposPermitidos->merge($descendientes);
                }

                if (!$todosLosGruposPermitidos->contains($request->grupo_id)) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['grupo_id' => 'El grupo seleccionado no pertenece a este cliente.']);
                }
            }
        }

        $usuario->nombre = $request->nombre;
        $usuario->email = $request->email;
        if ($request->password) {
            $usuario->password = bcrypt($request->password);
        }
        if ($request->has('role_id')) {
            $usuario->role_id = $request->role_id;
        }
        if ($request->has('grupo_id')) {
            $usuario->grupo_id = $request->grupo_id ?: null;
        }
        $usuario->save();

        return redirect()->route('usuarios.index', ['id' => $id])->with('success', 'Usuario actualizado correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id, User $usuario)
    {
        $usuario->delete();
        return redirect()->route('usuarios.index', ['id' => $id])->with('success', 'Usuario eliminado correctamente');
    }

    public function permissions($id, User $user)
    {
        /** @var User|null $currentUser */
        $currentUser = Auth::user();

        // Solo Super Administrador puede acceder a la gestión de permisos
        if (!$currentUser || !$currentUser->isSuperAdmin()) {
            abort(403, 'Solo el Super Administrador puede gestionar permisos de usuarios.');
        }

        $roles = Role::all();

        // Obtener estructura de menús del sidebar
        $menuStructure = $this->getMenuStructure();

        // Obtener permisos actuales del usuario
        $permissions = $user->menuPermissions()
            ->get()
            ->keyBy('menu_key')
            ->toArray();

        return view('clientes.usuarios.permissions', [
            "section_name" => "Permisos del usuario",
            "section_description" => "Configurar permisos de menú y roles del usuario",
            "usuario" => $user,
            "cliente_id" => $id,
            "roles" => $roles,
            "menuStructure" => $menuStructure,
            "permissions" => $permissions,
        ]);
    }

    /**
     * Obtener estructura de menús del sidebar
     */
    private function getMenuStructure()
    {
        return [
            [
                'key' => 'usuarios',
                'name' => 'Usuarios',
                'icon' => 'icon-user-tie',
                'submenus' => [
                    [
                        'key' => 'usuarios.clientes',
                        'name' => 'Usuarios',
                        'route' => 'clientes.index',
                        'icon' => 'icon-user-tie',
                    ],
                    [
                        'key' => 'usuarios.grupos',
                        'name' => 'Grupos',
                        'route' => 'grupos.index',
                        'icon' => 'icon-collaboration',
                    ],
                ],
            ],
            [
                'key' => 'estaciones',
                'name' => 'Estaciones de medición',
                'icon' => 'icon-station',
                'submenus' => [
                    [
                        'key' => 'estaciones.fabricantes',
                        'name' => 'Fabricantes',
                        'route' => 'fabricantes.index',
                        'icon' => 'icon-wrench3',
                    ],
                    [
                        'key' => 'estaciones.tipo_estacion',
                        'name' => 'Tipos de estaciones',
                        'route' => 'tipo_estacion.index',
                        'icon' => 'icon-satellite-dish2',
                    ],
                    [
                        'key' => 'estaciones.grupos',
                        'name' => 'Grupos',
                        'route' => 'grupos.index',
                        'icon' => 'icon-collaboration',
                    ],
                    [
                        'key' => 'estaciones.almacenes',
                        'name' => 'Almacenes',
                        'route' => 'almacenes.index',
                        'icon' => 'icon-home7',
                    ],
                    [
                        'key' => 'estaciones.alta',
                        'name' => 'Alta de estaciones',
                        'route' => 'estaciones.index',
                        'icon' => 'icon-station',
                    ],
                    [
                        'key' => 'estaciones.configuracion_mqtt',
                        'name' => 'Configuración MQTT',
                        'route' => 'configuracion-mqtt.login',
                        'icon' => 'icon-cog3',
                        'submenus' => [
                            [
                                'key' => 'estaciones.configuracion_mqtt.configuracion',
                                'name' => 'Configuración',
                                'route' => 'configuracion-mqtt.index',
                                'icon' => 'icon-cog3',
                            ],
                            [
                                'key' => 'estaciones.configuracion_mqtt.usuarios',
                                'name' => 'Usuarios',
                                'route' => 'configuracion-mqtt.usuarios.index',
                                'icon' => 'icon-users',
                            ],
                            [
                                'key' => 'estaciones.configuracion_mqtt.logs',
                                'name' => 'Logs',
                                'route' => 'configuracion-mqtt.logs',
                                'icon' => 'icon-list',
                            ],
                        ],
                    ],
                ],
            ],
            [
                'key' => 'parametros',
                'name' => 'Parámetros agronómicos',
                'icon' => 'icon-stats-dots',
                'submenus' => [
                    [
                        'key' => 'parametros.etapas_fenologicas',
                        'name' => 'Etapas fenológicas',
                        'route' => 'etapasfenologicas.index',
                        'icon' => 'icon-sun3',
                    ],
                    [
                        'key' => 'parametros.plagas',
                        'name' => 'Plagas',
                        'route' => 'plaga.index',
                        'icon' => 'icon-bug2',
                    ],
                    [
                        'key' => 'parametros.cultivos',
                        'name' => 'Cultivos',
                        'route' => 'cultivos.index',
                        'icon' => 'icon-fan',
                    ],
                    [
                        'key' => 'parametros.textura_suelo',
                        'name' => 'Textura de suelo',
                        'route' => 'textura-suelo.index',
                        'icon' => 'icon-cube4',
                    ],
                    [
                        'key' => 'parametros.enfermedades',
                        'name' => 'Enfermedades',
                        'route' => 'enfermedades.index',
                        'icon' => 'icon-aid-kit',
                    ],
                ],
            ],
        ];
    }

    public function UpdateRoleUser(Request $request, $id)
    {
        /** @var User|null $currentUser */
        $currentUser = Auth::user();

        // Solo Super Administrador puede actualizar permisos
        if (!$currentUser || !$currentUser->isSuperAdmin()) {
            abort(403, 'Solo el Super Administrador puede gestionar permisos de usuarios.');
        }

        $user = User::find($request->user_id);
        if (!$user) {
            return redirect()->back()->with('error', 'Usuario no encontrado.');
        }

        if (!$request->has('role_id')) {
            return redirect()->back()->with('error', 'El rol es requerido.');
        }

        $user->role_id = $request->role_id;
        $user->save();

        // Guardar permisos de menú
        // Eliminar permisos existentes del usuario
        UserMenuPermission::where('user_id', $user->id)->delete();

        // Obtener estructura de menús y guardar permisos
        $menuStructure = $this->getMenuStructure();
        $menuPermissions = $request->input('menu_permissions', []);
        $crudPermissions = $request->input('crud_permissions', []);

        foreach ($menuStructure as $mainMenu) {
            $mainMenuKey = $mainMenu['key'];
            // Verificar si el checkbox está marcado (valor = '1')
            // Si no existe en el array, significa que no está marcado (false)
            $mainPermitted = isset($menuPermissions[$mainMenuKey]) &&
                ($menuPermissions[$mainMenuKey] == '1' || $menuPermissions[$mainMenuKey] == 1);

            // Obtener permisos CRUD para el menú principal
            $mainCrud = $crudPermissions[$mainMenuKey] ?? [];
            $mainCanCreate = isset($mainCrud['create']) && ($mainCrud['create'] == '1' || $mainCrud['create'] == 1);
            $mainCanEdit = isset($mainCrud['edit']) && ($mainCrud['edit'] == '1' || $mainCrud['edit'] == 1);
            $mainCanDelete = isset($mainCrud['delete']) && ($mainCrud['delete'] == '1' || $mainCrud['delete'] == 1);

            // Guardar permiso de menú principal (siempre, incluso si está bloqueado)
            UserMenuPermission::create([
                'user_id' => $user->id,
                'menu_key' => $mainMenuKey,
                'menu_type' => 'main',
                'parent_key' => null,
                'permitted' => $mainPermitted,
                'can_create' => $mainPermitted && $mainCanCreate,
                'can_edit' => $mainPermitted && $mainCanEdit,
                'can_delete' => $mainPermitted && $mainCanDelete,
            ]);

            // Guardar permisos de secundarias (siempre, incluso si la principal está bloqueada)
            if (isset($mainMenu['submenus'])) {
                foreach ($mainMenu['submenus'] as $subMenu) {
                    $subMenuKey = $subMenu['key'];
                    // Si la principal está bloqueada, el submenú también debe estar bloqueado
                    // Si la principal está permitida, verificar el valor del checkbox
                    $subPermitted = $mainPermitted &&
                        isset($menuPermissions[$subMenuKey]) &&
                        ($menuPermissions[$subMenuKey] == '1' || $menuPermissions[$subMenuKey] == 1);

                    // Obtener permisos CRUD para el submenú
                    $subCrud = $crudPermissions[$subMenuKey] ?? [];
                    $subCanCreate = isset($subCrud['create']) && ($subCrud['create'] == '1' || $subCrud['create'] == 1);
                    $subCanEdit = isset($subCrud['edit']) && ($subCrud['edit'] == '1' || $subCrud['edit'] == 1);
                    $subCanDelete = isset($subCrud['delete']) && ($subCrud['delete'] == '1' || $subCrud['delete'] == 1);

                    // Guardar permiso de submenú (siempre)
                    UserMenuPermission::create([
                        'user_id' => $user->id,
                        'menu_key' => $subMenuKey,
                        'menu_type' => 'sub',
                        'parent_key' => $mainMenuKey,
                        'permitted' => $subPermitted,
                        'can_create' => $subPermitted && $subCanCreate,
                        'can_edit' => $subPermitted && $subCanEdit,
                        'can_delete' => $subPermitted && $subCanDelete,
                    ]);

                    // Manejar submenús anidados (como Configuración MQTT)
                    if (isset($subMenu['submenus'])) {
                        foreach ($subMenu['submenus'] as $nestedSubMenu) {
                            $nestedKey = $nestedSubMenu['key'];
                            // Si el padre está bloqueado, el anidado también debe estar bloqueado
                            // Si el padre está permitido, verificar el valor del checkbox
                            $nestedPermitted = $subPermitted &&
                                isset($menuPermissions[$nestedKey]) &&
                                ($menuPermissions[$nestedKey] == '1' || $menuPermissions[$nestedKey] == 1);

                            // Obtener permisos CRUD para el submenú anidado
                            $nestedCrud = $crudPermissions[$nestedKey] ?? [];
                            $nestedCanCreate = isset($nestedCrud['create']) && ($nestedCrud['create'] == '1' || $nestedCrud['create'] == 1);
                            $nestedCanEdit = isset($nestedCrud['edit']) && ($nestedCrud['edit'] == '1' || $nestedCrud['edit'] == 1);
                            $nestedCanDelete = isset($nestedCrud['delete']) && ($nestedCrud['delete'] == '1' || $nestedCrud['delete'] == 1);

                            // Guardar permiso de submenú anidado (siempre)
                            UserMenuPermission::create([
                                'user_id' => $user->id,
                                'menu_key' => $nestedKey,
                                'menu_type' => 'sub',
                                'parent_key' => $subMenuKey,
                                'permitted' => $nestedPermitted,
                                'can_create' => $nestedPermitted && $nestedCanCreate,
                                'can_edit' => $nestedPermitted && $nestedCanEdit,
                                'can_delete' => $nestedPermitted && $nestedCanDelete,
                            ]);
                        }
                    }
                }
            }
        }

        return redirect()->route('usuarios.index', ['id' => $id])->with('success', 'Permisos actualizados correctamente');
    }

    /**
     * Obtener los roles que el usuario actual puede asignar
     */
    private function getRolesPermitidos()
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return Role::where('id', '!=', 1)->get(); // Por defecto, excluir Super Administrador
        }

        // Super Administrador puede crear todos los roles
        if ($user->isSuperAdmin()) {
            return Role::all();
        }

        // Administrador y Cliente solo pueden crear Administradores (role_id = 2)
        // No pueden crear Super Administradores (role_id = 1)
        return Role::where('id', '!=', 1)->get(); // Excluir Super Administrador
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Clientes;
use App\Models\User;
use App\Models\Usuarios;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {

    $usuarios = Usuarios::all();
    //Validar el usuario si es superadmin o normal
    $usuario = Auth::user();
    $cliente_id = $usuario->cliente_id;

    if (!$usuario->isSuperAdmin()) {
        // Si no es superadmin, filtrar por cliente_id
        $usuarios = Usuarios::where('cliente_id', $cliente_id)->get();
    }

    // Obtener grupos disponibles según el usuario autenticado Y el cliente
    $user = Auth::user();

    return view('usuarios.index', [
        "section_name" => "Usuarios",
        "section_description" => "Listado de Usuarios",
        "usuarios" => $usuarios,
        "user" => $user,
    ]);
    }

    public function create()
    {
        //validar si el usuario es superadmin o normal
        $usuario = Auth::user();
        $cliente_id = $usuario->cliente_id;
        $clientes = Clientes::all();

        if($cliente_id != null){
            //si no es superadmin, redirigir a la vista de error o mostrar mensaje
            $clientes = Clientes::where('id', $cliente_id)->get();
        }

        $gruposDisponibles = \App\Models\Grupos::with('grupoPadre')
                    ->where('grupo_id', '!=', null)
                    ->get()
                    ->map(function ($grupo) {
                        return [
                            'id' => $grupo->id,
                            'nombre' => $grupo->ruta_completa,
                        ];
                    });

        
        return view('usuarios.create', [
            "section_name" => "Usuarios",
            "section_description" => "Crear Usuario",
            "clientes" => $clientes,
            "cliente_id" => $cliente_id,
            "gruposDisponibles" => $gruposDisponibles,
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'cliente_id' => 'nullable|integer|exists:clientes,id',
        ]);

        $usuario = Usuarios::create([
            'nombre' => $validatedData['nombre'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']),
            'role_id' => 3, // Asignar rol de usuario normal por defecto
            'cliente_id' => $validatedData['cliente_id'] ?? null,
            'status' => 1,
        ]);

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado exitosamente.');
    }   

    public function edit($id)
    {
        // Obtener grupos disponibles según el usuario autenticado Y el cliente
        // Obtener el usuario que editar y el usuario logeado
        $user = User::findOrFail($id);
        $clienteId =  $user->cliente_id;
        $usuarioLogeado = Auth::user();


        $clientes = Clientes::all();

        if($clienteId != null){
            //si no es superadmin, redirigir a la vista de error o mostrar mensaje
            $clientes = Clientes::where('id', $clienteId)->get();
        }

        // Si el usuario es super admin, puede ver todos los grupos
        // Si no, solo los grupos asignados al cliente
        if ($usuarioLogeado->isSuperAdmin()) {
            $gruposDisponibles = \App\Models\Grupos::with('grupoPadre')
                    ->where('grupo_id', '!=', null)
                    ->get()
                    ->map(function ($grupo) {
                        return [
                            'id' => $grupo->id,
                            'nombre' => $grupo->ruta_completa,
                        ];
                    });

            //dd($gruposDisponibles);
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

        dd($gruposDisponibles);

        //dd($clientes, $clienteId);

        return view('usuarios.edit', [
            "section_name" => "Editar Usuario",
            "section_description" => "Editar un usuario existente",
            "usuario" => $user,
            "cliente_id" => $clienteId,
            "gruposDisponibles" => $gruposDisponibles,
            "clientes" => $clientes,
        ]);

        dd('edit user '.$id);
    }
}

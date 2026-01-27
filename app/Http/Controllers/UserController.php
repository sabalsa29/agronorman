<?php

namespace App\Http\Controllers;

use App\Models\Clientes;
use App\Models\GrupoParcela;
use App\Models\GrupoZonaManejo;
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
        ]);

        $usuario = Usuarios::create([
            'nombre' => $validatedData['nombre'],
            'email' => $validatedData['email'],
            'password' => bcrypt($validatedData['password']),
            'role_id' => 3, // Asignar rol de usuario normal por defecto
            'cliente_id' => 0,
            'status' => 1,
        ]);

         // Asignar grupos al usuario 
        if($request->grupo_id) {
            foreach ($request->grupo_id as $grupoId) {
                $nuevoUserGrupo = new \App\Models\UserGrupo();
                $nuevoUserGrupo->user_id = $usuario->id;
                $nuevoUserGrupo->grupo_id = $grupoId;
                $nuevoUserGrupo->save();
            }
        }

          //Asignaciones manuales
        if ($request->filled('asignaciones_cache')) {

            $asignacionesCache = json_decode($request->input('asignaciones_cache'), true);

            if (!is_array($asignacionesCache)) {
                dd('asignaciones_cache inválido');
            }
            // Validar lo siguiente, si dentro de asignaciones_cache vienen predios y dentro de predios zonas vienen vacias
            // quiere decir que se creara un asignarPrediosAUsuario, y si dentro de zonas vienen datos, se creara un asignarZonasAUsuario
            // IDs de predios (grupo -> predios -> [id])
            // dentro de prediosIds solo van a ir los ids de los predios que en zonas vengan vacias

            $prediosIds = collect($asignacionesCache)
                ->pluck('predios')
                ->filter() // quita null
                ->flatMap(function ($predios) {
                    return collect($predios)
                        ->filter(function ($predio) {
                            return empty($predio['zonas']); // zonas vacío o no existe
                        })
                        ->pluck('id'); // o ->keys() si prefieres
                })
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->toArray();

            //dd($prediosIds);
            // IDs de zonas (grupo -> predios -> zonas -> [id])
            // Zonas con contexto (grupo_id, parcela_id, zona_id)
            $zonasAsignaciones = collect($asignacionesCache)
                ->flatMap(function ($grupo, $grupoKey) {

                    // El grupo puede venir con 'id' o usar la key del array
                    $grupoId = (int) ($grupo['id'] ?? $grupoKey);

                    $predios = $grupo['predios'] ?? [];

                    return collect($predios)->flatMap(function ($predio, $predioKey) use ($grupoId) {

                        // El predio/parcela puede venir con 'id' o usar la key del array
                        $parcelaId = (int) ($predio['id'] ?? $predioKey);

                        $zonas = $predio['zonas'] ?? [];

                        if (empty($zonas)) {
                            return collect(); // si no hay zonas, aquí no devuelve nada
                        }

                        return collect($zonas)->map(function ($zona, $zonaKey) use ($grupoId, $parcelaId) {

                            // La zona puede venir con 'id' o usar la key del array
                            $zonaId = (int) ($zona['id'] ?? $zonaKey);

                            return [
                                'zona_id'    => $zonaId,
                                'parcela_id' => $parcelaId,
                                'grupo_id'   => $grupoId,
                            ];
                        });
                    });
                })
                ->filter(fn ($row) => !empty($row['zona_id']))
                ->unique('zona_id')   // evita duplicados por zona
                ->values()
                ->toArray();

            
            // Guardar las asignaciones en la base de datos
            
                if(!empty($prediosIds)) {
                    \App\Models\GrupoParcela::asignarPrediosAUsuario($usuario->id, $prediosIds);
                }
            //Se debe agregar en las zonasIds la parcela_id a la que pertenece la zona y ademas el grupo_id al cual pertenece esa parcela
            //dd('las zonas id son ', $zonasAsignaciones);
            if (!empty($zonasAsignaciones)) {
                \App\Models\GrupoZonaManejo::asignarZonasAUsuario($usuario->id, $zonasAsignaciones);
            }

            //dd('los predios son ', $prediosIds, ' las zonas son: ', $zonasIds);
        }

        return redirect()->route('usuarios.index')->with('success', 'Productor creado exitosamente.');
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

        //Obtener los id de los grupos asignados al usuario
        $gruposAsignadosIds = $user->grupos->pluck('id')->map(fn($v)=>(string)$v)->toArray();

        /**
         * =========================
         * Precargar asignaciones manuales (árbol)
         * =========================
         * Estructura esperada:
         * {
         *   "2": { id:"2", nombre:"Grupo X", predios:{ "6":{id:"6",nombre:"Predio",zonas:{ "5":{...}} } } }
         * }
         */
        $asignacionesCache = [];

        // 1) Predios asignados manualmente (grupo_parcela)
       $parcelasRows = GrupoParcela::query()
            ->with(['grupo.grupoPadre', 'parcela'])
            ->where('user_id', $user->id)
            ->get() 
            ->map(function ($row) {
                return [
                    'grupo_id' => $row->grupo_id,
                    'grupo_nombre' => optional($row->grupo)->ruta_completa ?? 'Sin grupo1',
                    'parcela_id' => $row->parcela_id,
                    'parcela_nombre' => optional($row->parcela)->nombre,
                ];
            });


        foreach ($parcelasRows as $row) {
            $gidKey = (string)($row['grupo_id'] ?? 0); // 0 => "Sin grupo" si llega null

            if (!isset($asignacionesCache[$gidKey])) {
                $asignacionesCache[$gidKey] = [
                    'id' => $row['grupo_id'] ? (string)$row['grupo_id'] : null,
                    'nombre' => $row['grupo_nombre'] ?? 'Sin grupo2',
                    'predios' => [],
                ];
            }

            $pidKey = (string)$row['parcela_id'];

            if (!isset($asignacionesCache[$gidKey]['predios'][$pidKey])) {
                $asignacionesCache[$gidKey]['predios'][$pidKey] = [
                    'id' => (string)$row['parcela_id'],
                    'nombre' => $row['parcela_nombre'] ?? '',
                    'zonas' => [],
                ];
            }
        }


        // 2) Zonas asignadas manualmente (grupo_zona_manejo)
        $zonasRows = GrupoZonaManejo::query()
            ->with(['grupo.grupoPadre', 'zonaManejo.parcela'])
            ->where('user_id', $user->id)
            ->get();

        foreach ($zonasRows as $row) {
            $gidKey = (string)($row->grupo_id ?? 0);

            if (!isset($asignacionesCache[$gidKey])) {
                $asignacionesCache[$gidKey] = [
                    'id' => $row->grupo_id ? (string)$row->grupo_id : null,
                    'nombre' => optional($row->grupo)->ruta_completa ?? 'Sin grupo3',
                    'predios' => [],
                ];
            }

            $parcela = $row->zonaManejo?->parcela;
            if (!$parcela) {
                continue;
            }

            $pidKey = (string)$parcela->id;

            if (!isset($asignacionesCache[$gidKey]['predios'][$pidKey])) {
                $asignacionesCache[$gidKey]['predios'][$pidKey] = [
                    'id' => (string)$parcela->id,
                    'nombre' => $parcela->nombre,
                    'zonas' => [],
                ];
            }

            $zidKey = (string)$row->zona_manejo_id;
            $asignacionesCache[$gidKey]['predios'][$pidKey]['zonas'][$zidKey] = [
                'id' => (string)$row->zona_manejo_id,
                'nombre' => $row->zonaManejo?->nombre ?? '',
            ];
        }


        // Esto es lo que tu JS/partial lee para pintar el árbol al cargar el edit
        $asignaciones_cache = json_encode($asignacionesCache, JSON_UNESCAPED_UNICODE);

        //dd($asignaciones_cache);

        return view('usuarios.edit', [
            "section_name" => "Editar Usuario",
            "section_description" => "Editar un usuario existente",
            "usuario" => $user,
            "cliente_id" => $clienteId,
            "gruposDisponibles" => $gruposDisponibles,
            "clientes" => $clientes,
            "gruposAsignadosIds" => $gruposAsignadosIds,

            "asignaciones_cache" => $asignaciones_cache,
        ]);
    }

    public function update(Request $request, $id)
    {
        $usuario = User::findOrFail($id);

        // =========================
        // 1) Validación base usuario
        // =========================
        $validated = $request->validate([
            'nombre'     => 'required|string|max:255',
            'email'      => 'required|email|max:255|unique:users,email,' . $usuario->id,
            'password'   => 'nullable|string|min:8',
            'grupo_id'   => 'nullable|array',
            'grupo_id.*' => 'integer|exists:grupos,id',

            // asignaciones_cache puede venir:
            // - null / ""  => borrar todo
            // - JSON válido => actualizar
            'asignaciones_cache' => 'nullable|string',
        ]);

        // =========================
        // 2) Update usuario
        // =========================
        $usuario->nombre = $validated['nombre'];
        $usuario->email = $validated['email'];
       //$usuario->cliente_id = $validated['cliente_id'] ?? null;

        if (!empty($validated['password'])) {
            $usuario->password = bcrypt($validated['password']);
        }
        $usuario->save();

        // =========================
        // 3) Grupos (pivote user_grupo)
        // - si viene null/ausente => elimina todos
        // - si viene array => sync
        // =========================
        \App\Models\UserGrupo::where('user_id', $usuario->id)->delete();

        $grupoIds = $request->input('grupo_id', []);
        if (is_array($grupoIds) && !empty($grupoIds)) {
            foreach ($grupoIds as $grupoId) {
                \App\Models\UserGrupo::create([
                    'user_id' => $usuario->id,
                    'grupo_id' => (int)$grupoId,
                ]);
            }
        }

        // =========================
        // 4) Asignaciones manuales (grupo_parcela / grupo_zona_manejo)
        // Reglas:
        // - Si asignaciones_cache viene null/"" => eliminar TODO y terminar
        // - Si viene JSON => validar estructura, eliminar existentes y recrear
        // =========================
        $rawCache = $request->input('asignaciones_cache');

        //dd($rawCache);

        // Si viene explícitamente vacío o null => borrar todo
        if ($rawCache === null || trim((string)$rawCache) === '') {
            \App\Models\GrupoParcela::where('user_id', $usuario->id)->delete();
            \App\Models\GrupoZonaManejo::where('user_id', $usuario->id)->delete();

            return redirect()->route('usuarios.index')
                ->with('success', 'Usuario actualizado.');
        }

        // JSON decode
        $asignacionesCache = json_decode($rawCache, true);

        if (!is_array($asignacionesCache)) {
            return back()
                ->withInput()
                ->withErrors(['asignaciones_cache' => 'asignaciones_cache inválido: no es JSON válido.']);
        }

        // =========================

        // =========================
        // 4.2) Eliminar existentes ANTES de recrear
        // =========================
        \App\Models\GrupoParcela::where('user_id', $usuario->id)->delete();
        \App\Models\GrupoZonaManejo::where('user_id', $usuario->id)->delete();

        // =========================
        // 4.3) Extraer prediosIds sin zonas y zonasIds
        // - prediosIds: solo predios donde zonas esté vacío
        // - zonasIds: ids de zonas existentes
        // =========================
        $prediosIds = collect($asignacionesCache)
            ->pluck('predios')
            ->filter()
            ->flatMap(function ($predios) {
                return collect($predios)
                    ->filter(function ($predio) {
                        $zonas = $predio['zonas'] ?? [];
                        return empty($zonas);
                    })
                    ->pluck('id');
            })
            ->map(fn($id) => (int)$id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->toArray();

        // =========================
        // 4.4) Validar existencia en BD (recomendado)
        // =========================
        if (!empty($prediosIds)) {
            $validPredios = \App\Models\Parcelas::whereIn('id', $prediosIds)->pluck('id')->toArray();
            $prediosIds = array_values(array_unique(array_map('intval', $validPredios)));
        }

        // =========================
        // 4.5) Insertar (usa tus métodos)
        // Nota: tus métodos actualmente no manejan grupo_id.
        // Si necesitas grupo_id por asignación, debe venir en el método.
        // =========================
        if (!empty($prediosIds)) {
            \App\Models\GrupoParcela::asignarPrediosAUsuario($usuario->id, $prediosIds);
        }

         $zonasAsignaciones = collect($asignacionesCache)
                ->flatMap(function ($grupo, $grupoKey) {

                    $grupoId = (int) ($grupo['id'] ?? $grupoKey);
                    $predios = $grupo['predios'] ?? [];
                    return collect($predios)->flatMap(function ($predio, $predioKey) use ($grupoId) {

                        // El predio/parcela puede venir con 'id' o usar la key del array
                        $parcelaId = (int) ($predio['id'] ?? $predioKey);
                        $zonas = $predio['zonas'] ?? [];
                        if (empty($zonas)) {
                            return collect(); // si no hay zonas, aquí no devuelve nada
                        }
                        return collect($zonas)->map(function ($zona, $zonaKey) use ($grupoId, $parcelaId) {

                            // La zona puede venir con 'id' o usar la key del array
                            $zonaId = (int) ($zona['id'] ?? $zonaKey);
                            return [
                                'zona_id'    => $zonaId,
                                'parcela_id' => $parcelaId,
                                'grupo_id'   => $grupoId,
                            ];
                        });
                    });
                })
                ->filter(fn ($row) => !empty($row['zona_id']))
                ->unique('zona_id')   // evita duplicados por zona
                ->values()
                ->toArray();


        if (!empty($zonasAsignaciones)) {
            \App\Models\GrupoZonaManejo::asignarZonasAUsuario($usuario->id, $zonasAsignaciones);
        }

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado exitosamente.');
    }
}

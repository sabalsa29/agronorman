<?php

namespace App\Http\Controllers;

use App\Models\Grupos;
use Illuminate\Http\Request;

class GruposController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        // Obtener grupos accesibles al usuario
        $grupos = Grupos::forUser($user)->get();

        // Construir estructura jerárquica completa empezando desde grupos raíz
        $gruposRaiz = Grupos::forUser($user)
            ->whereNull('grupo_id')
            ->with(['subgrupos', 'zonaManejos', 'usuarios'])
            ->get();

        $estructuraJerarquica = collect();

        foreach ($gruposRaiz as $grupoRaiz) {
            $estructuraJerarquica->push($this->construirEstructuraGrupo($grupoRaiz, $user));
        }

        return view('grupos.index', [
            "section_name" => "Grupos",
            "section_description" => "Gestión de grupos jerárquicos",
            "list" => $grupos, // Mantener para compatibilidad
            "estructuraJerarquica" => $estructuraJerarquica,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $user = auth()->user();
        // Cargar grupos disponibles según el usuario (solo los que puede ver)
        $gruposDisponibles = Grupos::with('grupoPadre')
            ->forUser($user)
            ->get()
            ->map(function ($grupo) {
                return [
                    'id' => $grupo->id,
                    'nombre' => $grupo->ruta_completa,
                ];
            });

        // Si viene grupo_padre_id, pre-seleccionarlo
        $grupoPadreId = $request->get('grupo_padre_id');

        return view('grupos.create', [
            "section_name" => "Grupos",
            "section_description" => "Crear grupo",
            "gruposDisponibles" => $gruposDisponibles,
            "grupoPadreId" => $grupoPadreId,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'status' => 'nullable|boolean',
            'grupo_id' => 'nullable|exists:grupos,id',
        ]);

        $grupos = new Grupos();
        $grupos->nombre = $request->nombre;
        $grupos->status = $request->status ?? 1;
        $grupos->grupo_id = $request->grupo_id ?: null;
        $grupos->save();

        return redirect()->route('grupos.index')->with('success', 'Grupo creado correctamente.');
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Grupos $grupo)
    {
        $user = auth()->user();

        // Verificar que el usuario tenga acceso a este grupo
        if (!$grupo->userHasAccess($user)) {
            abort(403, 'No tiene permiso para acceder a este grupo.');
        }

        // Obtener grupos disponibles excluyendo el grupo actual y sus subgrupos
        // para evitar crear ciclos en la jerarquía
        $gruposExcluidos = $this->obtenerGruposExcluidos($grupo);

        $gruposDisponibles = Grupos::with('grupoPadre')
            ->forUser($user)
            ->whereNotIn('id', $gruposExcluidos)
            ->get()
            ->map(function ($g) {
                return [
                    'id' => $g->id,
                    'nombre' => $g->ruta_completa,
                ];
            });

        return view('grupos.edit', [
            "section_name" => "Grupos",
            "section_description" => "Editar grupo",
            "grupo" => $grupo,
            "gruposDisponibles" => $gruposDisponibles,
        ]);
    }

    /**
     * Obtener IDs de grupos que deben ser excluidos (el grupo actual y todos sus descendientes)
     */
    private function obtenerGruposExcluidos(Grupos $grupo)
    {
        $excluidos = [$grupo->id];

        // Obtener recursivamente todos los subgrupos
        $this->obtenerSubgruposRecursivo($grupo, $excluidos);

        return $excluidos;
    }

    /**
     * Obtener recursivamente todos los subgrupos de un grupo
     */
    private function obtenerSubgruposRecursivo(Grupos $grupo, &$excluidos)
    {
        // Cargar subgrupos si no están cargados
        if (!$grupo->relationLoaded('subgrupos')) {
            $grupo->load('subgrupos');
        }

        foreach ($grupo->subgrupos as $subgrupo) {
            $excluidos[] = $subgrupo->id;
            $this->obtenerSubgruposRecursivo($subgrupo, $excluidos);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Grupos $grupo)
    {
        $user = auth()->user();

        // Verificar que el usuario tenga acceso a este grupo
        if (!$grupo->userHasAccess($user)) {
            abort(403, 'No tiene permiso para editar este grupo.');
        }

        $request->validate([
            'nombre' => 'required|string|max:255',
            'status' => 'nullable|boolean',
            'grupo_id' => [
                'nullable',
                'exists:grupos,id',
                function ($attribute, $value, $fail) use ($grupo, $user) {
                    if ($value) {
                        // Verificar que no se esté asignando un subgrupo como padre
                        $gruposExcluidos = $this->obtenerGruposExcluidos($grupo);
                        if (in_array($value, $gruposExcluidos)) {
                            $fail('No puede asignar un grupo hijo o el mismo grupo como padre.');
                        }

                        // Verificar que el usuario tenga acceso al grupo padre seleccionado
                        $grupoPadre = Grupos::find($value);
                        if ($grupoPadre && !$grupoPadre->userHasAccess($user)) {
                            $fail('No tiene permiso para asignar este grupo como padre.');
                        }
                    }
                },
            ],
        ]);

        $grupo->nombre = $request->nombre;
        $grupo->status = $request->status ?? $grupo->status;
        $grupo->grupo_id = $request->grupo_id ?: null;
        $grupo->save();

        return redirect()->route('grupos.index')->with('success', 'Grupo actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Grupos $grupo)
    {
        $user = auth()->user();

        // Verificar que el usuario tenga acceso a este grupo
        if (!$grupo->userHasAccess($user)) {
            abort(403, 'No tiene permiso para eliminar este grupo.');
        }

        $grupo->delete();
        return redirect()->route('grupos.index')->with('success', 'Grupo eliminado correctamente.');
    }

    /**
     * Mostrar dashboard con estructura jerárquica de grupos
     * Solo accesible desde la vista de grupos (/grupos)
     */
    public function dashboard(Request $request)
    {
        $user = auth()->user();

        // Verificar que el usuario viene desde /grupos
        $referer = $request->headers->get('referer');
        $vieneDesdeGrupos = $referer && str_contains($referer, route('grupos.index', [], false));

        // También verificar si viene con parámetro desde_grupos
        $desdeGrupos = $request->get('desde_grupos', false);

        if (!$vieneDesdeGrupos && !$desdeGrupos) {
            // Si no viene desde grupos, redirigir a grupos
            return redirect()->route('grupos.index')
                ->with('info', 'El Dashboard de Grupos solo es accesible desde la vista de Grupos.');
        }

        // Obtener grupos raíz (sin padre) según el usuario
        $gruposRaiz = Grupos::with(['subgrupos', 'usuarios', 'zonaManejos'])
            ->whereNull('grupo_id')
            ->forUser($user)
            ->get();

        // Construir estructura jerárquica completa
        $estructura = [];
        foreach ($gruposRaiz as $grupo) {
            $estructura[] = $this->construirEstructuraGrupo($grupo, $user);
        }

        return view('grupos.dashboard', [
            "section_name" => "Dashboard de Grupos",
            "section_description" => "Vista jerárquica de grupos y usuarios asignados",
            "estructura" => $estructura,
        ]);
    }

    /**
     * Construir estructura recursiva de un grupo con sus usuarios y zonas
     */
    private function construirEstructuraGrupo(Grupos $grupo, $user = null)
    {
        // Cargar relaciones necesarias
        $grupo->load(['usuarios', 'zonaManejos']);

        // Cargar subgrupos recursivamente (hasta 5 niveles de profundidad)
        $this->cargarSubgruposRecursivo($grupo, 0, 5);

        $estructura = [
            'id' => $grupo->id,
            'nombre' => $grupo->nombre,
            'status' => $grupo->status,
            'usuarios' => $grupo->usuarios->map(function ($usuario) {
                return [
                    'id' => $usuario->id,
                    'nombre' => $usuario->nombre,
                    'email' => $usuario->email,
                ];
            })->toArray(),
            'zonas_manejo' => $grupo->zonaManejos->map(function ($zona) {
                return [
                    'id' => $zona->id,
                    'nombre' => $zona->nombre,
                ];
            })->toArray(),
            'subgrupos' => [],
        ];

        // Construir subgrupos recursivamente
        foreach ($grupo->subgrupos as $subgrupo) {
            // Verificar que el usuario tenga acceso al subgrupo
            if (!$user || $user->isSuperAdmin() || $subgrupo->userHasAccess($user)) {
                $estructura['subgrupos'][] = $this->construirEstructuraGrupo($subgrupo, $user);
            }
        }

        return $estructura;
    }

    /**
     * Cargar subgrupos recursivamente con todas sus relaciones
     */
    private function cargarSubgruposRecursivo(Grupos $grupo, $profundidad = 0, $maxProfundidad = 10)
    {
        if ($profundidad >= $maxProfundidad) {
            return; // Prevenir recursión infinita
        }

        // Cargar subgrupos del nivel actual
        $grupo->load(['subgrupos.usuarios', 'subgrupos.zonaManejos']);

        // Recursivamente cargar subgrupos de cada subgrupo
        foreach ($grupo->subgrupos as $subgrupo) {
            $this->cargarSubgruposRecursivo($subgrupo, $profundidad + 1, $maxProfundidad);
        }
    }

    /**
     * Mostrar zonas de manejo del usuario de forma simplificada
     */
    public function zonasManejo(Request $request)
    {
        $user = auth()->user();

        // Obtener todas las zonas de manejo del usuario primero
        $todasLasZonas = \App\Models\ZonaManejos::with('grupo')
            ->forUser($user)
            ->get();

        // Determinar el grupo del usuario y todos sus ancestros
        $grupoUsuario = null;
        $gruposAncestros = collect(); // Todos los grupos hacia arriba (fijos)
        $grupoRaiz = null;
        $todasLasZonasParaConteo = $todasLasZonas;

        if ($user->isSuperAdmin()) {
            //dd($todasLasZonas);
            // Super admin: si hay múltiples grupos raíz, usar el primero o el seleccionado
            $grupoFiltroRaiz = $request->get('grupo_raiz_id');
          
            if ($grupoFiltroRaiz) {
                $grupoRaiz = Grupos::find($grupoFiltroRaiz);
                $grupoUsuario = $grupoRaiz; // Para super admin, el grupo raíz es su "grupo usuario"
            } else { 
                // Obtener el primer grupo raíz disponible
                $grupoRaiz = Grupos::whereNull('grupo_id')->first();
                //dd($grupoRaiz);
                $grupoUsuario = $grupoRaiz;
            }
            $todasLasZonasParaConteo = \App\Models\ZonaManejos::with('grupo')->get();
            //dd($todasLasZonasParaConteo);
        } elseif ($user->grupo_id) {

            // Usuario con grupo: obtener el grupo del usuario y todos sus ancestros
            $grupoUsuario = $user->grupo;
            if (!$grupoUsuario) {
                // Si el grupo no existe, no hay nada que mostrar
                $zonasManejo = collect();
                return view('grupos.zonas-manejo', [
                    "section_name" => "Mis Zonas de Manejo",
                    "section_description" => "Seleccione una zona de manejo para ver su información completa",
                    "zonasManejo" => $zonasManejo,
                    "gruposAncestros" => collect(),
                    "grupoUsuario" => null,
                    "subgrupos" => collect(),
                    "subgrupoFiltro" => null,
                    "busqueda" => '',
                    "user" => $user,
                ]);
            }

            $grupoRaiz = $grupoUsuario;

            // Obtener todos los ancestros (hacia arriba hasta la raíz)
            $grupoActual = $grupoUsuario->grupoPadre;
            while ($grupoActual) {
                $gruposAncestros->prepend($grupoActual); // Agregar al inicio para mantener orden
                $grupoRaiz = $grupoActual;
                $grupoActual = $grupoActual->grupoPadre;
            }
        } else {
            // Usuario sin grupo: obtener grupos de las zonas y subir hasta la raíz
            $gruposIds = $todasLasZonas->pluck('grupo_id')->filter()->unique();
            if ($gruposIds->count() > 0) {
                $gruposDeZonas = Grupos::whereIn('id', $gruposIds)->get();
                // Tomar el primer grupo y subir hasta la raíz
                $primerGrupo = $gruposDeZonas->first();
                if ($primerGrupo) {
                    $grupoUsuario = $primerGrupo;
                    $grupoRaiz = $primerGrupo;

                    // Obtener todos los ancestros
                    $grupoActual = $primerGrupo->grupoPadre;
                    while ($grupoActual) {
                        $gruposAncestros->prepend($grupoActual);
                        $grupoRaiz = $grupoActual;
                        $grupoActual = $grupoActual->grupoPadre;
                    }
                }
            }
        }

        // Si no hay grupo usuario, no hay nada que mostrar
        if (!$grupoUsuario) {
            $zonasManejo = collect();
            return view('grupos.zonas-manejo', [
                "section_name" => "Mis Zonas de Manejo",
                "section_description" => "Seleccione una zona de manejo para ver su información completa",
                "zonasManejo" => $zonasManejo,
                "gruposAncestros" => collect(),
                "grupoUsuario" => null,
                "subgrupos" => collect(),
                "subgrupoFiltro" => null,
            ]);
        }

        // Cargar subgrupos del grupo del usuario (solo descendientes, no ancestros)
        // Cargar recursivamente todos los subgrupos para construir el árbol completo
        // También cargar grupoPadre para construir rutas completas
        $grupoUsuario->load(['subgrupos' => function ($query) {
            // Cargar subgrupos recursivamente (hasta 3 niveles) y sus relaciones de grupoPadre
            $query->with(['subgrupos' => function ($q) {
                $q->with(['subgrupos', 'grupoPadre' => function ($q2) {
                    $q2->with('grupoPadre'); // Cargar hasta la raíz
                }]);
            }, 'grupoPadre' => function ($q) {
                $q->with('grupoPadre'); // Cargar hasta la raíz
            }]);
        }]);

        // Para el conteo, necesitamos todas las zonas accesibles al usuario
        // Usar el scope forUser() que ya incluye zonas del grupo, descendientes y asignadas directamente
        if (!$user->isSuperAdmin()) {
            $todasLasZonasParaConteo = \App\Models\ZonaManejos::with('grupo')
                ->forUser($user)
                ->get();
        } else {
            $todasLasZonasParaConteo = \App\Models\ZonaManejos::with('grupo')->get();
        }

        // Construir el árbol de subgrupos accesibles al usuario
        // Si el grupo del usuario tiene un padre, mostrar todos los hermanos accesibles
        // Si el grupo del usuario es raíz, mostrar todos sus subgrupos
        if ($grupoUsuario->grupoPadre) {
            // El usuario está en un subgrupo, mostrar todos los hermanos accesibles
            $grupoPadre = $grupoUsuario->grupoPadre;
            // Cargar subgrupos con sus relaciones de grupoPadre para construir rutas completas
            $grupoPadre->load(['subgrupos.grupoPadre' => function ($query) {
                $query->with('grupoPadre'); // Cargar recursivamente hasta la raíz
            }]);

            $subgrupos = collect();
            foreach ($grupoPadre->subgrupos as $subgrupo) {
                // Para hermanos, verificar directamente si hay zonas accesibles al usuario
                // que pertenezcan a este subgrupo o sus descendientes
                $gruposDescendientes = collect($subgrupo->obtenerDescendientes());

                // Consultar directamente las zonas accesibles al usuario de este subgrupo
                $zonasDelSubgrupo = \App\Models\ZonaManejos::with('grupo')
                    ->forUser($user)
                    ->whereIn('grupo_id', $gruposDescendientes)
                    ->get();

                $totalZonas = $zonasDelSubgrupo->count();

                // Incluir el subgrupo si:
                // 1. Es super admin
                // 2. Tiene zonas accesibles al usuario
                // 3. Es el grupo del usuario mismo (para mostrar estructura completa)
                if ($user->isSuperAdmin() || $totalZonas > 0 || $subgrupo->id == $grupoUsuario->id) {
                    // Usar la ruta completa para mostrar la jerarquía completa
                    $rutaCompleta = $subgrupo->ruta_completa;
                    $prefijo = $subgrupo->id == $grupoUsuario->id ? '★ ' : '';
                    $item = [
                        'id' => $subgrupo->id,
                        'nombre' => $subgrupo->nombre,
                        'nombre_completo' => $prefijo . $rutaCompleta,
                        'zona_manejos_count' => $totalZonas,
                        'nivel' => 0,
                        'subgrupos' => $this->construirArbolSubgrupos($subgrupo, $todasLasZonasParaConteo, $user, 1, '  '),
                    ];

                    $subgrupos->push($item);
                }
            }
        } else {
            // El grupo del usuario es raíz, mostrar todos sus subgrupos
            $subgrupos = $this->construirArbolSubgrupos($grupoUsuario, $todasLasZonasParaConteo, $user);
        }

        // Obtener el filtro de subgrupo seleccionado
        $subgrupoFiltro = $request->get('subgrupo_id');

        // Obtener el término de búsqueda
        $busqueda = $request->get('busqueda', '');

        // Obtener todas las zonas de manejo del usuario (basado en su grupo)
        // El scope forUser() ya filtra correctamente por grupo y descendientes
        $query = \App\Models\ZonaManejos::with(['parcela.cliente', 'tipoCultivos', 'grupo'])
            ->forUser($user);

        // Aplicar filtro según si hay un subgrupo seleccionado o no
        if ($subgrupoFiltro) {
            // Si hay un subgrupo seleccionado, filtrar solo por ese subgrupo y sus descendientes
            $subgrupoSeleccionado = Grupos::find($subgrupoFiltro);
            if ($subgrupoSeleccionado) {
                // Verificar que el subgrupo sea accesible al usuario (hermano o descendiente)
                $esAccesible = false;

                // Verificar si es hermano del grupo del usuario
                if ($grupoUsuario->grupoPadre && $subgrupoSeleccionado->grupoPadre) {
                    $esAccesible = $subgrupoSeleccionado->grupoPadre->id == $grupoUsuario->grupoPadre->id;
                }

                // Verificar si es descendiente del grupo del usuario
                if (!$esAccesible) {
                    $gruposPermitidos = collect($grupoUsuario->obtenerDescendientes());
                    $esAccesible = $gruposPermitidos->contains($subgrupoSeleccionado->id);
                }

                if ($esAccesible) {
                    // Filtrar por el subgrupo seleccionado y sus descendientes
                    $gruposPermitidos = collect($subgrupoSeleccionado->obtenerDescendientes());
                    $query->whereIn('grupo_id', $gruposPermitidos);
                } else {
                    // Si no es accesible, no mostrar nada
                    $query->whereRaw('1 = 0');
                }
            }
        } else {
            // Por defecto (sin filtro de subgrupo), mostrar solo las zonas del grupo del usuario mismo
            // NO incluir zonas de hermanos ni de subgrupos descendientes
            $query->where('grupo_id', $grupoUsuario->id);
        }

        $zonasManejo = $query->get()
            ->map(function ($zona) use ($user) {
                // Obtener el primer tipo de cultivo disponible
                $tipoCultivo = $zona->tipoCultivos()->first();
                $tipoCultivoId = $tipoCultivo ? $tipoCultivo->id : null;

                // Obtener la primera etapa fenológica del tipo de cultivo
                $etapaFenologicaId = null;
                if ($tipoCultivoId) {
                    $etapaFenologica = \App\Models\EtapaFenologicaTipoCultivo::where('tipo_cultivo_id', $tipoCultivoId)
                        ->orderBy('id')
                        ->first();
                    $etapaFenologicaId = $etapaFenologica ? $etapaFenologica->etapa_fenologica_id : null;
                }

                return [
                    'id' => $zona->id,
                    'nombre' => $zona->nombre,
                    'parcela' => $zona->parcela ? $zona->parcela->nombre : 'Sin parcela',
                    'cliente' => $zona->parcela && $zona->parcela->cliente ? $zona->parcela->cliente->nombre : 'Sin cliente',
                    'cliente_id' => $zona->parcela && $zona->parcela->cliente ? $zona->parcela->cliente->id : null,
                    'parcela_id' => $zona->parcela_id,
                    'tipo_cultivo_id' => $tipoCultivoId,
                    'tipo_cultivo_nombre' => $tipoCultivo ? $tipoCultivo->nombre : 'Sin tipo de cultivo',
                    'etapa_fenologica_id' => $etapaFenologicaId,
                    'grupo' => $zona->grupo ? $zona->grupo->nombre : null,
                    'grupo_id' => $zona->grupo_id,
                ];
            })
            ->filter(function ($zona) {
                // Solo mostrar zonas que tengan todos los datos necesarios
                return $zona['cliente_id'] && $zona['parcela_id'] && $zona['tipo_cultivo_id'] && $zona['etapa_fenologica_id'];
            })
            ->filter(function ($zona) use ($busqueda) {
                // Aplicar filtro de búsqueda si existe
                if (empty($busqueda)) {
                    return true;
                }

                $terminoBusqueda = strtolower(trim($busqueda));

                // Buscar en nombre de zona, cliente, parcela, tipo de cultivo y grupo
                return (
                    str_contains(strtolower($zona['nombre']), $terminoBusqueda) ||
                    str_contains(strtolower($zona['cliente']), $terminoBusqueda) ||
                    str_contains(strtolower($zona['parcela']), $terminoBusqueda) ||
                    str_contains(strtolower($zona['tipo_cultivo_nombre']), $terminoBusqueda) ||
                    ($zona['grupo'] && str_contains(strtolower($zona['grupo']), $terminoBusqueda))
                );
            })
            ->values();

        return view('grupos.zonas-manejo', [
            "section_name" => "Mis Zonas de Manejo",
            "section_description" => "Seleccione una zona de manejo para ver su información completa",
            "zonasManejo" => $zonasManejo,
            "gruposAncestros" => $gruposAncestros,
            "grupoUsuario" => $grupoUsuario,
            "subgrupos" => $subgrupos,
            "subgrupoFiltro" => $subgrupoFiltro,
            "busqueda" => $busqueda,
            "user" => $user,
        ]);
    }

    /**
     * Construir árbol de subgrupos con formato jerárquico
     */
    private function construirArbolSubgrupos(Grupos $grupo, $todasLasZonas, $user, $nivel = 0, $prefijo = '')
    {
        $resultado = collect();

        // Cargar subgrupos si no están cargados, incluyendo grupoPadre para rutas completas
        if (!$grupo->relationLoaded('subgrupos')) {
            $grupo->load(['subgrupos.grupoPadre' => function ($query) {
                $query->with('grupoPadre'); // Cargar recursivamente hasta la raíz
            }]);
        }

        foreach ($grupo->subgrupos as $index => $subgrupo) {
            // Verificar que el usuario tenga acceso al subgrupo
            if (!$user->isSuperAdmin() && !$subgrupo->userHasAccess($user)) {
                continue; // Saltar subgrupos sin acceso
            }

            // Contar zonas del subgrupo (incluyendo zonas de subgrupos descendientes)
            $gruposDescendientes = collect($subgrupo->obtenerDescendientes());
            $zonasDelSubgrupo = $todasLasZonas->filter(function ($zona) use ($gruposDescendientes) {
                // Acceder a la propiedad grupo_id del modelo
                $zonaGrupoId = is_object($zona) ? $zona->grupo_id : ($zona['grupo_id'] ?? null);
                return $zonaGrupoId && $gruposDescendientes->contains($zonaGrupoId);
            });
            $totalZonas = $zonasDelSubgrupo->count();

            // Incluir subgrupo si el usuario tiene acceso (mostrar todos los subgrupos accesibles)
            // Esto permite que el usuario vea la estructura completa aunque algunos subgrupos no tengan zonas

            // Usar la ruta completa para mostrar la jerarquía completa
            $rutaCompleta = $subgrupo->ruta_completa;
            $prefijoVisual = $prefijo . '  '; // Indentación para subgrupos anidados

            $item = [
                'id' => $subgrupo->id,
                'nombre' => $subgrupo->nombre,
                'nombre_completo' => $prefijoVisual . $rutaCompleta,
                'zona_manejos_count' => $totalZonas,
                'nivel' => $nivel,
                'subgrupos' => $this->construirArbolSubgrupos($subgrupo, $todasLasZonas, $user, $nivel + 1, $prefijoVisual),
            ];

            $resultado->push($item);
        }

        return $resultado;
    }
}

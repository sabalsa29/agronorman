<?php

namespace App\Http\Controllers;

use App\Models\GrupoParcela;
use App\Models\Grupos;
use App\Models\GrupoZonaManejo;
use App\Models\UserGrupo;
use App\Models\ZonaManejos;
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

        // Cargar solo los grupos donde su id_grupo sea igual al id del grupo donde is_root es true 
        // Esto para cargar desde el grupo raíz "norman"
        $gruposRaiz = Grupos::forUser($user)
            ->where('grupo_id', Grupos::where('is_root', true)->first()->id)
            ->where('is_root', false)
            ->with(['subgrupos', 'zonaManejos', 'usuarios'])
            ->get();

        //dd($gruposRaiz);
            

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
        // Carga todos los grupos a excepción del grupo raíz "norman" si el usuario no es superadmin 
        $gruposDisponibles = Grupos::with('grupoPadre')
            ->forUser($user)
            ->where('is_root', false)
            ->get()
            ->map(function ($grupo) {
                return [
                    'id' => $grupo->id,
                    'nombre' => $grupo->ruta_completa,
                ];
            });
        //dd($gruposDisponibles);
        // Si viene grupo_padre_id, pre-seleccionarlo
        $grupoPadreId = $request->get('grupo_padre_id');

        //dd($grupoPadreId, $gruposDisponibles);

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
        //Validar si grupo_id es null, poner por default el id deel grupo raiz 'norman'
        
        //dd($request->all());
        $request->validate([
            'nombre' => 'required|string|max:255',
            'status' => 'nullable|boolean',
            'grupo_id' => 'nullable|exists:grupos,id',
        ]);
        $id_norman = Grupos::where('is_root', true)->first()->id;

        $grupos = new Grupos();
        $grupos->nombre = $request->nombre;
        $grupos->status = $request->status ?? 1;
        $grupos->grupo_id = $request->grupo_id ?: $id_norman;
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
            ->where('grupo_id', Grupos::where('is_root', true)->first()->id)
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

        $id_norman = Grupos::where('is_root', true)->first()->id;

        $grupo->nombre = $request->nombre;
        $grupo->status = $request->status ?? $grupo->status;
        $grupo->grupo_id = $request->grupo_id ?: $id_norman;
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


     public function zonasManejossa(Request $request)
    {
        $user = auth()->user();
        $esAdmin = $user && $user->isSuperAdmin();
        //dd($user);
        // =========================
        // 1) Resolver accesos (NO admin)
        // =========================
        $gruposRelacionIds = collect();     // IDs de grupos relacionados (pivot + asignaciones)
        $gruposPermitidos  = collect();       // grupos relacionados + descendientes
        $parcelasAsignadasIds = collect();    // parcelas asignadas (directo o por zona)
        $zonasAsignadasIds = collect();       // zonas asignadas individualmente

        if (!$esAdmin && $user) {

            // Grupos por relación directa (pivot user_grupo)
            $gruposPivotIds = $user->grupos()->pluck('grupos.id');

            // Parcelas asignadas directamente al usuario (grupo_parcela)
            $asigPredios = \App\Models\GrupoParcela::query()
                ->where('user_id', $user->id)
                ->get(['grupo_id', 'parcela_id']);

            // Zonas asignadas directamente al usuario (grupo_zona_manejo)
            $asigZonas = \App\Models\GrupoZonaManejo::query()
                ->where('user_id', $user->id)
                ->get(['grupo_id', 'parcela_id', 'zona_manejo_id']); // ajusta si tu columna se llama distinto

            // Parcelas relacionadas (por asignación de parcela o por zona)
            $parcelasAsignadasIds = $asigPredios->pluck('parcela_id')
                ->merge($asigZonas->pluck('parcela_id'))
                ->filter()
                ->unique()
                ->values();

            // Zonas relacionadas (asignadas individualmente)
            $zonasAsignadasIds = $asigZonas->pluck('zona_manejo_id')
                ->filter()
                ->unique()
                ->values();

            // Grupos relacionados (pivot + asignaciones)
            $gruposRelacionIds = $gruposPivotIds
                ->merge($asigPredios->pluck('grupo_id'))
                ->merge($asigZonas->pluck('grupo_id'))
                ->filter()
                ->unique()
                ->values();

            // Usuario sin relación => no mostrar datos
            if ($gruposRelacionIds->isEmpty() && $parcelasAsignadasIds->isEmpty() && $zonasAsignadasIds->isEmpty()) {
                return view('grupos.zonas-manejo', [
                    "section_name" => "Mis Zonas de Manejo",
                    "section_description" => "Seleccione una zona de manejo para ver su información completa",
                    "zonasManejo" => collect(),
                    "gruposAncestros" => collect(),
                    "grupoUsuario" => null,
                    "subgrupos" => collect(),
                    "subgrupoFiltro" => null,
                    "busqueda" => $request->get('busqueda', ''),
                    "user" => $user,
                ]);
            }

            // Expandir grupos a descendientes para mostrar TODO el contenido del/los grupos asignados
            if ($gruposRelacionIds->isNotEmpty()) {
                $gruposRelacion = Grupos::whereIn('id', $gruposRelacionIds)->get();

                $gruposPermitidos = $gruposRelacion
                    ->flatMap(function ($g) {
                        $desc = $g->obtenerDescendientes(); // idealmente IDs
                        return collect($desc)->map(function ($x) {
                            return is_object($x) ? (int) $x->id : (int) $x;
                        });
                    })
                    ->filter()
                    ->unique()
                    ->values();
            }
        }

        // =========================
        // 2) Query base: zonas accesibles
        // =========================
        $zonasAccesiblesQuery = function () use ($esAdmin, $gruposPermitidos, $parcelasAsignadasIds, $zonasAsignadasIds) {
            $q = \App\Models\ZonaManejos::query();

            // ✅ Admin: ve TODO (sin depender de user_grupo)
            if ($esAdmin) {
                return $q;
            }

            // No admin: por grupos (y descendientes) + parcelas asignadas + zonas asignadas
            return $q->where(function ($qq) use ($gruposPermitidos, $parcelasAsignadasIds, $zonasAsignadasIds) {
                if ($gruposPermitidos->isNotEmpty()) {
                    $qq->orWhereIn('grupo_id', $gruposPermitidos);
                }
                if ($parcelasAsignadasIds->isNotEmpty()) {
                    $qq->orWhereIn('parcela_id', $parcelasAsignadasIds);
                }
                if ($zonasAsignadasIds->isNotEmpty()) {
                    $qq->orWhereIn('id', $zonasAsignadasIds);
                }
            });
        };

        // =========================
        // 3) Zonas accesibles (para conteos/árbol)
        // =========================
        $todasLasZonas = $zonasAccesiblesQuery()
            ->with('grupo')
            ->get();

        $todasLasZonasParaConteo = $todasLasZonas;

        // =========================
        // 4) Determinar grupoUsuario + ancestros + raíz
        // =========================
        $grupoUsuario = null;
        $gruposAncestros = collect();
        $grupoRaiz = null;

        if ($esAdmin) {
            //dd('es admin');
            // ✅ Admin: puede ver TODOS los grupos aunque NO estén en user_grupo.
            // Regla adicional: "Norman" solo aparece si es admin.
            // Si no mandan grupo_raiz_id, intentamos anclar en "Norman" (si existe).
            $grupoFiltroRaiz = $request->get('grupo_raiz_id');
            if ($grupoFiltroRaiz) {
                $grupoUsuario = Grupos::find($grupoFiltroRaiz);
            } else {
                $grupoNorman = Grupos::where('nombre', 'Norman')->first();
                $grupoUsuario = $grupoNorman ?: Grupos::whereNull('grupo_id')->first();
            }

            $grupoRaiz = $grupoUsuario;

            //dd($grupoRaiz);

            // Conteo para admin: todas las zonas
            $todasLasZonasParaConteo = \App\Models\ZonaManejos::with('grupo')->get();

            // Ancestros (si el admin eligió un subgrupo como raíz)
            if ($grupoUsuario) {
                $grupoActual = $grupoUsuario->grupoPadre;
                while ($grupoActual) {
                    $gruposAncestros->prepend($grupoActual);
                    $grupoRaiz = $grupoActual;
                    $grupoActual = $grupoActual->grupoPadre;
                }
            }

        } elseif ($user) {
            // No admin: ancla del árbol = uno de los grupos relacionados
            $grupoFiltro = (int) $request->get('grupo_raiz_id');

            $grupoBaseId = null;
            if ($grupoFiltro && $gruposRelacionIds->contains($grupoFiltro)) {
                $grupoBaseId = $grupoFiltro;
            } else {
                $grupoBaseId = $gruposRelacionIds->first();
            }

            if ($grupoBaseId) {
                $grupoUsuario = Grupos::find($grupoBaseId);
            }

            // Si aún no hay grupo, no mostrar nada
            if (!$grupoUsuario) {
                return view('grupos.zonas-manejo', [
                    "section_name" => "Mis Zonas de Manejo",
                    "section_description" => "Seleccione una zona de manejo para ver su información completa",
                    "zonasManejo" => collect(),
                    "gruposAncestros" => collect(),
                    "grupoUsuario" => null,
                    "subgrupos" => collect(),
                    "subgrupoFiltro" => null,
                    "busqueda" => $request->get('busqueda', ''),
                    "user" => $user,
                ]);
            }

            $grupoRaiz = $grupoUsuario;

            // Ancestros hacia arriba hasta raíz
            $grupoActual = $grupoUsuario->grupoPadre;
            while ($grupoActual) {
                $gruposAncestros->prepend($grupoActual);
                $grupoRaiz = $grupoActual;
                $grupoActual = $grupoActual->grupoPadre;
            }

            // Conteo para NO admin: solo accesibles
            $todasLasZonasParaConteo = $zonasAccesiblesQuery()
                ->with('grupo')
                ->get();
        }

        // Si no hay grupoUsuario y NO es admin, no hay nada que mostrar
        if (!$grupoUsuario && !$esAdmin) {
            return view('grupos.zonas-manejo', [
                "section_name" => "Mis Zonas de Manejo",
                "section_description" => "Seleccione una zona de manejo para ver su información completa",
                "zonasManejo" => collect(),
                "gruposAncestros" => collect(),
                "grupoUsuario" => null,
                "subgrupos" => collect(),
                "subgrupoFiltro" => null,
                "busqueda" => $request->get('busqueda', ''),
                "user" => $user,
            ]);
        }

        // =========================
        // 5) Cargar árbol de subgrupos (ancla)
        // =========================
        if ($grupoUsuario) {
            $grupoUsuario->load(['subgrupos' => function ($query) {
                $query->with([
                    'subgrupos' => function ($q) {
                        $q->with([
                            'subgrupos',
                            'grupoPadre' => function ($q2) {
                                $q2->with('grupoPadre');
                            }
                        ]);
                    },
                    'grupoPadre' => function ($q) {
                        $q->with('grupoPadre');
                    }
                ]);
            }]);
        }

        // Construir árbol de subgrupos
        if ($grupoUsuario && $grupoUsuario->grupoPadre) {

            $grupoPadre = $grupoUsuario->grupoPadre;

            $grupoPadre->load(['subgrupos.grupoPadre' => function ($query) {
                $query->with('grupoPadre');
            }]);

            $subgrupos = collect();

            foreach ($grupoPadre->subgrupos as $subgrupo) {
                $gruposDescendientes = collect($subgrupo->obtenerDescendientes())
                    ->map(fn($x) => is_object($x) ? (int) $x->id : (int) $x);

                $zonasDelSubgrupo = $zonasAccesiblesQuery()
                    ->with('grupo')
                    ->whereIn('grupo_id', $gruposDescendientes)
                    ->get();

                $totalZonas = $zonasDelSubgrupo->count();

                // ✅ Admin: incluir aunque no tenga zonas
                if ($esAdmin || $totalZonas > 0 || $subgrupo->id == $grupoUsuario->id) {
                    $rutaCompleta = $subgrupo->ruta_completa;
                    $prefijo = $subgrupo->id == $grupoUsuario->id ? '★ ' : '';

                    $subgrupos->push([
                        'id' => $subgrupo->id,
                        'nombre' => $subgrupo->nombre,
                        'nombre_completo' => $prefijo . $rutaCompleta,
                        'zona_manejos_count' => $totalZonas,
                        'nivel' => 0,
                        'subgrupos' => $this->construirArbolSubgrupos($subgrupo, $todasLasZonasParaConteo, $user, 1, '  '),
                    ]);
                }
            }

        } else {
            // ✅ Admin: construye árbol completo desde el ancla (Norman o raíz seleccionada)
            $subgrupos = $grupoUsuario
                ? $this->construirArbolSubgrupos($grupoUsuario, $todasLasZonasParaConteo, $user)
                : collect();

            //dd($subgrupos);
        }

        // =========================
        // 6) Filtros (subgrupo + búsqueda)
        // =========================
        $subgrupoFiltro = $request->get('subgrupo_id');
        $busqueda = $request->get('busqueda', '');

        $query = $zonasAccesiblesQuery()
            ->with(['parcela.cliente', 'tipoCultivos', 'grupo']);

        if ($subgrupoFiltro) {
            $subgrupoSeleccionado = Grupos::find($subgrupoFiltro);

            if ($subgrupoSeleccionado) {
                $esAccesible = $esAdmin ? true : $gruposPermitidos->contains($subgrupoSeleccionado->id);

                if ($esAccesible) {
                    $gruposFiltro = collect($subgrupoSeleccionado->obtenerDescendientes())
                        ->map(fn($x) => is_object($x) ? (int) $x->id : (int) $x)
                        ->filter()
                        ->unique()
                        ->values();

                    $query->whereIn('grupo_id', $gruposFiltro);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
        }

        // =========================
        // 7) Mapear para la vista + búsqueda (incluye grupo)
        // =========================
        $zonasManejo = $query->get()
            ->map(function ($zona) {
                $tipoCultivo = $zona->tipoCultivos()->first();
                $tipoCultivoId = $tipoCultivo ? $tipoCultivo->id : null;

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
                // Solo mostrar zonas con datos mínimos para dashboard
                return $zona['parcela_id'] && $zona['tipo_cultivo_id'] && $zona['etapa_fenologica_id'];
            })
            ->filter(function ($zona) use ($busqueda) {
                if (empty($busqueda)) return true;

                $t = strtolower(trim($busqueda));

                // ✅ búsqueda sobre grupo, parcela, cliente, cultivo y zona
                return (
                    str_contains(strtolower($zona['nombre']), $t) ||
                    str_contains(strtolower($zona['cliente'] ?? ''), $t) ||
                    str_contains(strtolower($zona['parcela'] ?? ''), $t) ||
                    str_contains(strtolower($zona['tipo_cultivo_nombre'] ?? ''), $t) ||
                    str_contains(strtolower($zona['grupo'] ?? ''), $t)
                );
            })
            ->values();

        // =========================
        // 8) Retorno a la vista
        // =========================
        // Validar si es admin para mostrar grupos raíz, todos los grupos. si no es admin solo los grupos relacionados al usuario
        // Se debe ignorar el grupo raíz "norman" si el usuario no es admin
        if ($esAdmin) {
            $gruposRaiz = UserGrupo::all();
        } else {
            $gruposRaiz = UserGrupo::forUser($user)
                ->where('user_id', $user->id)
                ->get();
        }
        //$gruposRaiz = $user->grupos()->get();
        //dd($gruposRaiz);
        $data = [
            "section_name" => "Mis Zonas de Manejo",
            "section_description" => "Seleccione una zona de manejo para ver su información completa",
            "zonasManejo" => $zonasManejo,
            "gruposAncestros" => $gruposAncestros,
            "grupoUsuario" => $grupoUsuario,
            "subgrupos" => $subgrupos,
            "subgrupoFiltro" => $subgrupoFiltro,
            "gruposRaiz" => $gruposRaiz,
            "busqueda" => $busqueda,
            "user" => $user,
        ];

        //dd($data);

        return view('grupos.zonas-manejo', $data);
    }

    public function zonasManejoss(Request $request)
{
    $user = auth()->user();
    $esAdmin = $user && $user->isSuperAdmin();

    // =========================
    // 1) Resolver accesos (NO admin)
    // =========================
    $gruposRelacionIds = collect();       // IDs de grupos relacionados (pivot + asignaciones)
    $gruposPermitidos = collect();        // grupos relacionados + descendientes
    $parcelasAsignadasIds = collect();    // parcelas asignadas (directo o por zona)
    $zonasAsignadasIds = collect();       // zonas asignadas individualmente

    if (!$esAdmin && $user) {

        // Grupos por relación directa (pivot)
        $gruposPivotIds = $user->grupos()->pluck('grupos.id');

        // Parcelas asignadas
        $asigPredios = \App\Models\GrupoParcela::query()
            ->where('user_id', $user->id)
            ->get(['grupo_id', 'parcela_id']);

        // Zonas asignadas individualmente
        $asigZonas = \App\Models\GrupoZonaManejo::query()
            ->where('user_id', $user->id)
            ->get(['grupo_id', 'parcela_id', 'zona_manejo_id']); // ajusta si tu columna es distinta

        $parcelasAsignadasIds = $asigPredios->pluck('parcela_id')
            ->merge($asigZonas->pluck('parcela_id'))
            ->filter()
            ->unique()
            ->values();

        $zonasAsignadasIds = $asigZonas->pluck('zona_manejo_id')
            ->filter()
            ->unique()
            ->values();

        $gruposRelacionIds = $gruposPivotIds
            ->merge($asigPredios->pluck('grupo_id'))
            ->merge($asigZonas->pluck('grupo_id'))
            ->filter()
            ->unique()
            ->values();

        // Usuario sin relación => no mostrar datos
        if ($gruposRelacionIds->isEmpty() && $parcelasAsignadasIds->isEmpty() && $zonasAsignadasIds->isEmpty()) {
            return view('grupos.zonas-manejo', [
                "section_name" => "Mis Zonas de Manejo",
                "section_description" => "Seleccione una zona de manejo para ver su información completa",
                "zonasManejo" => collect(),
                "gruposAncestros" => collect(),
                "grupoUsuario" => null,
                "subgrupos" => collect(),
                "subgrupoFiltro" => null,
                "busqueda" => $request->get('busqueda', ''),
                "user" => $user,
            ]);
        }

        // Expandir grupos a descendientes para permitir navegación/árbol
        if ($gruposRelacionIds->isNotEmpty()) {
            $gruposRelacion = Grupos::whereIn('id', $gruposRelacionIds)->get();
            $gruposPermitidos = $gruposRelacion
                ->flatMap(fn($g) => $g->obtenerDescendientes()) // debe devolver IDs incluyendo el propio
                ->unique()
                ->values();
        }
    }

    // Closure de query base: devuelve zonas accesibles
    $zonasAccesiblesQuery = function () use ($esAdmin, $gruposPermitidos, $parcelasAsignadasIds, $zonasAsignadasIds) {
        $q = \App\Models\ZonaManejos::query();

        if ($esAdmin) {
            return $q;
        }

        return $q->where(function ($qq) use ($gruposPermitidos, $parcelasAsignadasIds, $zonasAsignadasIds) {
            if ($gruposPermitidos->isNotEmpty()) {
                $qq->orWhereIn('grupo_id', $gruposPermitidos);
            }
            if ($parcelasAsignadasIds->isNotEmpty()) {
                $qq->orWhereIn('parcela_id', $parcelasAsignadasIds);
            }
            if ($zonasAsignadasIds->isNotEmpty()) {
                $qq->orWhereIn('id', $zonasAsignadasIds);
            }
        });
    };

    // =========================
    // 2) Zonas accesibles (para conteos/árbol)
    // =========================
    $todasLasZonas = $zonasAccesiblesQuery()
        ->with('grupo')
        ->get();

    $todasLasZonasParaConteo = $todasLasZonas;

    // =========================
    // 3) Determinar grupoUsuario + ancestros + raíz
    // =========================
    $grupoUsuario = null;
    $gruposAncestros = collect();
    $grupoRaiz = null;

    if ($esAdmin) {
        // Admin: puede ver todo. El "grupoUsuario" se usa como ancla del árbol
        $grupoFiltroRaiz = $request->get('grupo_raiz_id');

        if ($grupoFiltroRaiz) {
            $grupoRaiz = Grupos::find($grupoFiltroRaiz);
            $grupoUsuario = $grupoRaiz;
        } else {
            $grupoRaiz = Grupos::whereNull('grupo_id')->first();
            $grupoUsuario = $grupoRaiz;
        }

        // Conteo para admin: todo
        $todasLasZonasParaConteo = \App\Models\ZonaManejos::with('grupo')->get();

    } elseif ($user) {
        // No admin: ancla del árbol = uno de los grupos relacionados (o por asignación)

        $grupoFiltro = (int) $request->get('grupo_raiz_id');

        $grupoBaseId = null;
        if ($grupoFiltro && $gruposRelacionIds->contains($grupoFiltro)) {
            $grupoBaseId = $grupoFiltro;
        } else {
            $grupoBaseId = $gruposRelacionIds->first();
        }

        if ($grupoBaseId) {
            $grupoUsuario = Grupos::find($grupoBaseId);
        }

        // Si no hay grupo por pivot, pero sí por asignación (caso raro), intenta con los permitidos
        if (!$grupoUsuario && $gruposPermitidos->isNotEmpty()) {
            $grupoUsuario = Grupos::find($gruposPermitidos->first());
        }

        // Si aún no hay grupo, no se muestra nada (porque el usuario solo tenía asignaciones sin grupo válido)
        if (!$grupoUsuario) {
            return view('grupos.zonas-manejo', [
                "section_name" => "Mis Zonas de Manejo",
                "section_description" => "Seleccione una zona de manejo para ver su información completa",
                "zonasManejo" => collect(),
                "gruposAncestros" => collect(),
                "grupoUsuario" => null,
                "subgrupos" => collect(),
                "subgrupoFiltro" => null,
                "busqueda" => $request->get('busqueda', ''),
                "user" => $user,
            ]);
        }

        $grupoRaiz = $grupoUsuario;

        // Ancestros hacia arriba hasta raíz
        $grupoActual = $grupoUsuario->grupoPadre;
        while ($grupoActual) {
            $gruposAncestros->prepend($grupoActual);
            $grupoRaiz = $grupoActual;
            $grupoActual = $grupoActual->grupoPadre;
        }

        // Conteo para NO admin: solo accesibles
        $todasLasZonasParaConteo = $zonasAccesiblesQuery()
            ->with('grupo')
            ->get();
    }

    // Si no hay grupoUsuario (por ejemplo, no hay grupos en BD), no hay árbol; pero admin igual ve zonas
    if (!$grupoUsuario && !$esAdmin) {
        return view('grupos.zonas-manejo', [
            "section_name" => "Mis Zonas de Manejo",
            "section_description" => "Seleccione una zona de manejo para ver su información completa",
            "zonasManejo" => collect(),
            "gruposAncestros" => collect(),
            "grupoUsuario" => null,
            "subgrupos" => collect(),
            "subgrupoFiltro" => null,
            "busqueda" => $request->get('busqueda', ''),
            "user" => $user,
        ]);
    }

    // =========================
    // 4) Cargar árbol de subgrupos (si hay ancla)
    // =========================
    if ($grupoUsuario) {
        $grupoUsuario->load(['subgrupos' => function ($query) {
            $query->with([
                'subgrupos' => function ($q) {
                    $q->with([
                        'subgrupos',
                        'grupoPadre' => function ($q2) {
                            $q2->with('grupoPadre');
                        }
                    ]);
                },
                'grupoPadre' => function ($q) {
                    $q->with('grupoPadre');
                }
            ]);
        }]);
    }

    // Construir árbol de subgrupos
    if ($grupoUsuario && $grupoUsuario->grupoPadre) {
        $grupoPadre = $grupoUsuario->grupoPadre;

        $grupoPadre->load(['subgrupos.grupoPadre' => function ($query) {
            $query->with('grupoPadre');
        }]);

        $subgrupos = collect();

        foreach ($grupoPadre->subgrupos as $subgrupo) {
            $gruposDescendientes = collect($subgrupo->obtenerDescendientes());

            $zonasDelSubgrupo = $zonasAccesiblesQuery()
                ->with('grupo')
                ->whereIn('grupo_id', $gruposDescendientes)
                ->get();

            $totalZonas = $zonasDelSubgrupo->count();

            if ($esAdmin || $totalZonas > 0 || $subgrupo->id == $grupoUsuario->id) {
                $rutaCompleta = $subgrupo->ruta_completa;
                $prefijo = $subgrupo->id == $grupoUsuario->id ? '★ ' : '';

                $subgrupos->push([
                    'id' => $subgrupo->id,
                    'nombre' => $subgrupo->nombre,
                    'nombre_completo' => $prefijo . $rutaCompleta,
                    'zona_manejos_count' => $totalZonas,
                    'nivel' => 0,
                    'subgrupos' => $this->construirArbolSubgrupos($subgrupo, $todasLasZonasParaConteo, $user, 1, '  '),
                ]);
            }
        }
    } else {
        $subgrupos = $grupoUsuario
            ? $this->construirArbolSubgrupos($grupoUsuario, $todasLasZonasParaConteo, $user)
            : collect();
    }

    // =========================
    // 5) Filtros de vista (subgrupo + búsqueda)
    // =========================
    $subgrupoFiltro = $request->get('subgrupo_id');
    $busqueda = $request->get('busqueda', '');

    // Query principal (zonas accesibles + relaciones necesarias)
    $query = $zonasAccesiblesQuery()
        ->with(['parcela.cliente', 'tipoCultivos', 'grupo']);
    //dd($query);

    // Filtro por subgrupo seleccionado (si aplica)
    if ($subgrupoFiltro) {
        $subgrupoSeleccionado = Grupos::find($subgrupoFiltro);

        if ($subgrupoSeleccionado) {
            $esAccesible = false;

            if ($esAdmin) {
                $esAccesible = true;
            } else {
                // Accesible si está dentro de los grupos permitidos (relacionados + descendientes)
                $esAccesible = $gruposPermitidos->contains($subgrupoSeleccionado->id);
            }

            if ($esAccesible) {
                $gruposFiltro = collect($subgrupoSeleccionado->obtenerDescendientes());
                $query->whereIn('grupo_id', $gruposFiltro);
            } else {
                $query->whereRaw('1 = 0');
            }
        }
    } else {
        // Sin subgrupo seleccionado:
        // - Admin: ver todo (sin filtro adicional)
        // - No admin: ver todo lo accesible (sin filtro adicional, la base ya filtra)
    }

    // =========================
    // 6) Mapear para la vista + filtros finales
    // =========================
    $zonasManejo = $query->get()
        ->map(function ($zona) use ($user) {
            $tipoCultivo = $zona->tipoCultivos()->first();
            $tipoCultivoId = $tipoCultivo ? $tipoCultivo->id : null;

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
            return $zona['parcela_id'] && $zona['tipo_cultivo_id'] && $zona['etapa_fenologica_id'];
        })
        ->filter(function ($zona) use ($busqueda) {
            if (empty($busqueda)) {
                return true;
            }

            $terminoBusqueda = strtolower(trim($busqueda));

            return (
                str_contains(strtolower($zona['nombre']), $terminoBusqueda) ||
                str_contains(strtolower($zona['cliente']), $terminoBusqueda) ||
                str_contains(strtolower($zona['parcela']), $terminoBusqueda) ||
                str_contains(strtolower($zona['tipo_cultivo_nombre']), $terminoBusqueda) ||
                ($zona['grupo'] && str_contains(strtolower($zona['grupo']), $terminoBusqueda))
            );
        })
        ->values();

    // =========================
    // 7) Retorno a la vista
    // =========================

    $data =[
        "section_name" => "Mis Zonas de Manejo",
        "section_description" => "Seleccione una zona de manejo para ver su información completa",
        "zonasManejo" => $zonasManejo,
        "gruposAncestros" => $gruposAncestros,
        "grupoUsuario" => $grupoUsuario,
        "subgrupos" => $subgrupos,
        "subgrupoFiltro" => $subgrupoFiltro,
        "busqueda" => $busqueda,
        "user" => $user,
        
    ];

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

    public function zonasManejos(Request $request)
    {
        $user = auth()->user();
        // Obtener todas las zonas de manejo del usuario primero
        // Modificar porque ahora va a cargar todas las zonas accesibles al usuario
        // incluyendo las asignadas directamente y las de sus grupos y descendientes
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
            // Mediante el usuario obtenemos los grupos que se encuentra asociados
            // Al igual las que estan relacionadas de forma manual
            $grupoUsuario = $user->grupo;

            //dd($grupoUsuario);
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
            //dd($zonasManejo);
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

        //dd($query->toSql(), $query->getBindings());

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
        //dd($zonasManejo);
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

    public function zonasManejo(Request $request)
    {
        $user = auth()->user();
        $esAdmin = $user && $user->isSuperAdmin();

        // Inicializar para evitar "undefined"
        $gruposRaiz  = collect();
        $parcelas    = collect();
        $zonasManejo = collect();

        if ($esAdmin) {

            // ✅ Admin: toma parcelas/pivots globales
            // validar que parcelas no se repitan en grupo_id 
            $parcelas = GrupoParcela::get();
            $parcelas = $parcelas->unique(fn($p) => ($p->grupo_id ?? '0').'|'.($p->parcela_id ?? '0'))
                ->values();

            // OJO: aquí estás trayendo "raíces" bajo grupo_id = 1 (Norman)
            $gruposRaiz = Grupos::where('is_root', false)
                ->where('grupo_id', 1)
                ->get();
            //dd($parcelas);
            // Zonas reales a partir de las parcelas (una sola query, sin N+1)
            $parcelaIds = $parcelas->pluck('parcela_id')->filter()->unique()->values();

            //dd($parcelaIds);

            $zonas = ZonaManejos::whereIn('parcela_id', $parcelaIds)
                ->get(['id', 'parcela_id', 'grupo_id']);

            // Mapa parcela_id => grupo_id desde grupo_parcela (para forzar el grupo del pivot)
            $grupoByParcela = $parcelas
                ->filter(fn ($gp) => !empty($gp->parcela_id))
                ->keyBy('parcela_id');

            $zonasManejo = $zonas->map(function ($z) use ($user, $grupoByParcela) {
                $pivot = new GrupoZonaManejo();

                $pivot->user_id = $user->id;

                $gp = $grupoByParcela->get($z->parcela_id);
                $pivot->grupo_id = $gp ? $gp->grupo_id : $z->grupo_id;

                // aunque no esté en $fillable, se puede asignar como atributo
                $pivot->parcela_id = $z->parcela_id;

                $pivot->zona_manejo_id = $z->id;

                return $pivot;
            });

            // (Opcional) Si quieres también incluir los registros reales existentes en grupo_zona_manejo:
            // $zonasManejo = $zonasManejo->merge(GrupoZonaManejo::get());

            $zonasManejo = $zonasManejo
                ->unique(fn ($i) => ($i->grupo_id ?? '0').'|'.($i->parcela_id ?? '0').'|'.$i->zona_manejo_id)
                ->values();

        } else {

          // Grupos raíz del usuario desde user_grupo
            $userGrupoIds = UserGrupo::where('user_id', $user->id)
                ->pluck('grupo_id')
                ->filter()
                ->unique()
                ->values();

            $gruposRaiz = Grupos::whereIn('id', $userGrupoIds)->get();

            // Validar gruposRaiz, si alguno de esos grupos raiz son hijos de genesis directo, osea en grupo_id = 1
            // entonces traer todos sus hijos tambien, ademas de cargar las parcelas y zonas adicionales
            $gruposRaiz = $gruposRaiz->map(function($g) {
                if ($g->grupo_id == 1) {
                    // Traer todos sus hijos
                    $hijos = Grupos::where('grupo_id', $g->id)->get();
                    return collect([$g])->merge($hijos);
                }
                return collect([$g]);
            })->flatten(1)->unique('id')->values();

            // Agregar las parcelas de los subgrupos hijos de los grupos raíz del usuario
            $parcelasDeSubgrupos = GrupoParcela::whereIn('grupo_id', $gruposRaiz->pluck('id'))->get();
            $parcelasGru = $parcelas->merge($parcelasDeSubgrupos)->unique(fn($p) => ($p->grupo_id ?? '0').'|'.($p->parcela_id ?? '0'))->values();

            $parcelasUser = GrupoParcela::where('user_id', $user->id)->get();

            //todas las parcelas
            $parcelas = $parcelasGru->merge($parcelasUser)
                ->unique(fn($p) => ($p->grupo_id ?? '0').'|'.($p->parcela_id ?? '0'))
                ->values();

            $parcelaIds = $parcelas->pluck('parcela_id')->filter()->unique()->values();

            $zonasPorParcelas = $parcelaIds->isEmpty()
                ? collect()
                : ZonaManejos::whereIn('parcela_id', $parcelaIds)->get(['id','parcela_id','grupo_id']);

            // mapa parcela_id => grupo_id desde grupo_parcela para forzar el grupo del pivot
            $grupoByParcela = $parcelas->filter(fn($gp)=>$gp->parcela_id)->keyBy('parcela_id');


            $zonasPivotDesdeParcelas = $zonasPorParcelas->map(function($z) use ($user, $grupoByParcela){
                $pivot = new GrupoZonaManejo();
                $pivot->user_id = $user->id;

                $gp = $grupoByParcela->get($z->parcela_id);
                $pivot->grupo_id = $gp ? $gp->grupo_id : $z->grupo_id;

                $pivot->parcela_id = $z->parcela_id;
                $pivot->zona_manejo_id = $z->id;

                return $pivot;
            });

            //dd($zonasPivotDesdeParcelas);

            // ==========================
            // 2) Zonas asignadas directamente (grupo_zona_manejo)
            //    Aseguramos parcela_id para que tu árbol funcione.
            // ==========================
            $zonaIdsDirectas = $zonasPivotDesdeParcelas->pluck('zona_manejo_id')->filter()->unique()->values();

            $infoZonasDirectas = $zonaIdsDirectas->isEmpty()
                ? collect()
                : ZonaManejos::whereIn('id', $zonaIdsDirectas)->get(['id', 'parcela_id', 'grupo_id']);

            $infoZonasDirectasById = $infoZonasDirectas->keyBy('id');

            $zonasPivotDirectasNormalizadas = $zonasPivotDesdeParcelas->map(function ($pz) use ($user, $infoZonasDirectasById) {
                $info = $infoZonasDirectasById->get($pz->zona_manejo_id);

                $pivot = new GrupoZonaManejo();

                $pivot->user_id = $user->id;
                $pivot->zona_manejo_id = $pz->zona_manejo_id;

                // si en tu tabla pivot existe grupo_id úsalo; si no, usa el de zona_manejos
                $pivot->grupo_id = $pz->grupo_id ?? ($info->grupo_id ?? null);

                // MUY importante para el árbol (zonasByParcela)
                $pivot->parcela_id = $pz->parcela_id ?? ($info->parcela_id ?? null);

                return $pivot;
            });
            //dd($zonasPivotDirectasNormalizadas);

            // ==========================
            // 3) Unir y deduplicar
            // ==========================
            $zonasManejo = $zonasPivotDirectasNormalizadas;

            //dd($zonasManejo);
                        // ==========================
            // (Opcional) Si quieres incluir TODOS los subgrupos descendientes
            // de los grupos raíz del usuario en la lista de grupos (para tu árbol):
            // ==========================
            if ($gruposRaiz->isNotEmpty()) {
                $allIds = $gruposRaiz->pluck('id')->values();
                $queue  = $allIds->values();

                //dd($allIds, $queue);

                while ($queue->isNotEmpty()) {
                    $childIds = Grupos::whereIn('grupo_id', $queue)->pluck('id')->values();
                    $newIds = $childIds->diff($allIds)->values();
                    if ($newIds->isEmpty()) break;
                    $allIds = $allIds->merge($newIds)->unique()->values();
                    $queue  = $newIds;
                }

                //dd($allIds);

                // Re-cargar todos para que tu vista pueda armar el árbol completo
                $gruposRaiz = Grupos::whereIn('id', $allIds)->get();
            }
            //Debemos obtener las parcelas de los grupos raiz y sus zonas de manejo
            // Obtener las parcelas de los grupos raíz
            $parcelasGruposRaiz = GrupoParcela::whereIn('grupo_id', $gruposRaiz->pluck('id'))->get();
            
            //dd($parcelasGruposRaiz);

           $zonasManejoUsuario= GrupoZonaManejo::where('user_id', $user->id)->get();

           $zonasManejo = $zonasManejo->merge($zonasManejoUsuario)
            ->unique(fn($i) => ($i->grupo_id ?? '0').'|'.($i->parcela_id ?? '0').'|'.$i->zona_manejo_id)
            ->values();

            // Obtener las parcelas de las zonas de zonasManejo finales
            $parcelaIdsFinales = $zonasManejo->pluck('parcela_id')->filter()->unique()->values();
            $parcelas = GrupoParcela::whereIn('parcela_id', $parcelaIdsFinales)->get();
            $parcelas = $parcelas->merge($parcelasDeSubgrupos)->unique(fn($p) => ($p->grupo_id ?? '0').'|'.($p->parcela_id ?? '0'))->values();
            //validar que parcelas no se repitan
            $parcelas = $parcelas->unique(fn($p) => ($p->grupo_id ?? '0').'|'.($p->parcela_id ?? '0'))
            ->values();

            //obtener zonas de manejo de parcelas finales
            $parcelaIds = $parcelas->pluck('parcela_id')->filter()->unique()->values();
            $zonas = ZonaManejos::whereIn('parcela_id', $parcelaIds)
                ->get(['id', 'parcela_id', 'grupo_id']);

            $zonasManejo = $zonas->map(function ($z) use ($user, $grupoByParcela) {
                $pivot = new GrupoZonaManejo();

                $pivot->user_id = $user->id;

                $gp = $grupoByParcela->get($z->parcela_id);
                $pivot->grupo_id = $gp ? $gp->grupo_id : $z->grupo_id;

                $pivot->parcela_id = $z->parcela_id;
                $pivot->zona_manejo_id = $z->id;

                return $pivot;
            })->unique(fn($i) => ($i->grupo_id ?? '0').'|'.($i->parcela_id ?? '0').'|'.$i->zona_manejo_id);

            //dd($zonasManejo)->values();
        }

        $data = [
            "section_name" => "Mis Zonas de Manejo",
            "section_description" => "Seleccione una zona de manejo para ver su información completa",
            "zonasManejo" => $zonasManejo,
            "parcelas" => $parcelas,
            "gruposRaiz" => $gruposRaiz,
            "user" => $user,
        ];

        //dd($data);

        return view('grupos.zonas-manejo', $data);
    }


        public function zonasManejoxs(Request $request)
        {
            $user = auth()->user();

            $esAdmin = $user && $user->isSuperAdmin();

        if ($esAdmin) {

        $parcelas = GrupoParcela::get();

        $gruposRaiz = Grupos::where('is_root', false)
            ->where('grupo_id', 1)
            ->get();

        $parcelaIds = $parcelas->pluck('parcela_id')->filter()->unique()->values();

        // Traer todas las zonas de todas las parcelas
        $zonas = ZonaManejos::whereIn('parcela_id', $parcelaIds)
            ->get(['id', 'parcela_id', 'grupo_id']);

        // Mapa parcela_id => grupo_id desde grupo_parcela (si quieres forzar el grupo del pivot)
        $grupoByParcela = $parcelas
            ->filter(fn($gp) => $gp->parcela_id)
            ->keyBy('parcela_id');

        $zonasManejo = $zonas->map(function ($z) use ($user, $grupoByParcela) {
            $pivot = new GrupoZonaManejo();

            $pivot->user_id = $user->id;

            $gp = $grupoByParcela->get($z->parcela_id);
            $pivot->grupo_id = $gp ? $gp->grupo_id : $z->grupo_id;

            $pivot->parcela_id = $z->parcela_id;
            $pivot->zona_manejo_id = $z->id;

            return $pivot;
        })->unique(fn($i) => ($i->grupo_id ?? '0').'|'.($i->parcela_id ?? '0').'|'.$i->zona_manejo_id)
        ->values();
    }
    else {
                $zonasManejoGrupos = GrupoZonaManejo::where('user_id', $user->id)->get();
                $parcelas = GrupoParcela::where('user_id', $user->id)->get();
                $userGrupos = UserGrupo::where('user_id', $user->id)
                ->get();

                // se recorre el foreach para obtener los grupos raiz
                foreach ($userGrupos as $userGrupo) {
                    // se iran agregando a GruposRaiz los grupos raiz del usuario
                $gruposRaiz[] = Grupos::where('is_root', false)->where('id', $userGrupo->id)
                    ->get();
                }

                //Recorrer grupoZonaManejo para obtener las zonas de manejo
                foreach ($zonasManejoGrupos as $zonaManejo) {
                    $zonasManejo[] = ZonaManejos::where('id', $zonaManejo->zona_manejo_id)
                    ->get();
                }
            }

        $data = [
            "section_name" => "Mis Zonas de Manejo",
            "section_description" => "Seleccione una zona de manejo para ver su información completa",
            "zonasManejo" => $zonasManejo,
            "parcelas" => $parcelas,
            "gruposRaiz" => $gruposRaiz,
            "user" => $user,
        ];

        return view('grupos.zonas-manejo', [
            "section_name" => "Mis Zonas de Manejo",
            "section_description" => "Seleccione una zona de manejo para ver su información completa",
            "parcelas" => $parcelas,
            "zonasManejo" => $zonasManejo,
            "grupoUsuario" => $gruposRaiz,
            "gruposRaiz" => $gruposRaiz,
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

    public function subgruposJson(\App\Models\Grupos $grupo)
{
    $subgrupos = \App\Models\Grupos::query()
        ->where('grupo_id', $grupo->id)
        ->orderBy('nombre')
        ->get(['id', 'nombre']);

    return response()->json([
        'subgrupos' => $subgrupos,
    ]);
}

public function parcelasJson(\App\Models\Grupos $grupo)
{
    $parcelas = \App\Models\GrupoParcela::query()
        ->where('grupo_id', $grupo->id)
        ->with(['parcela:id,nombre']) // si existe la relación
        ->get()
        ->map(function ($gp) {
            $id = (int) ($gp->parcela_id ?? $gp->id);
            $nombre = $gp->parcela->nombre ?? $gp->nombre ?? ("Parcela #".$id);

            return ['id' => $id, 'nombre' => $nombre];
        })
        ->unique('id')
        ->values();

    return response()->json([
        'parcelas' => $parcelas,
    ]);
}

}

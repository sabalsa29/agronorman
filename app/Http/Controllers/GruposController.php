<?php

namespace App\Http\Controllers;

use App\Models\GrupoParcela;
use App\Models\Grupos;
use App\Models\GrupoZonaManejo;
use App\Models\UserGrupo;
use App\Models\ZonaManejos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function zonasManejo(Request $request)
    {

        $user = auth()->user();
        $esAdmin = $user && $user->isSuperAdmin();

        // Inicializar para evitar "undefined"
        $gruposRaiz  = collect();
        $parcelas    = collect();
        $zonasManejo = collect();

        if ($esAdmin) {

            // validar que parcelas no se repitan en grupo_id 
            $parcelas = GrupoParcela::get();
            $parcelas = $parcelas->unique(fn($p) => ($p->grupo_id ?? '0').'|'.($p->parcela_id ?? '0'))
                ->values();

            $gruposRaiz = Grupos::where('is_root', false)
                ->where('grupo_id', 1)
                ->get();

            // Zonas reales a partir de las parcelas (una sola query, sin N+1)
            $parcelaIds = $parcelas->pluck('parcela_id')->filter()->unique()->values();

            // ✅ TRAER ZONAS + TIPOS DE CULTIVO (relación) para evitar N+1
            // Constarl tambien el cliente id para la zona, esta enlazado del modelo parcela que esta enlazado la zona
          $zonas = ZonaManejos::whereIn('parcela_id', $parcelaIds)
                ->select(['id', 'parcela_id', 'grupo_id']) // 👈 OBLIGATORIO incluir parcela_id
                ->with([
                    'tipoCultivos' => function ($q) {
                        $q->orderBy('tipo_cultivos.id');
                    },
                    'parcelaRel:id,cliente_id', // 👈 eager load con columnas necesarias
                ])
                ->get();

       
            // ✅ Sacar el primer tipo_cultivo_id por zona (como hacías con ->first())
            $tipoCultivoIds = $zonas->map(function ($z) {
                    return optional($z->tipoCultivos->first())->id;
                })
                ->filter()
                ->unique()
                ->values();

            // ✅ Mapa: tipo_cultivo_id => primera etapa_fenologica_id (por id asc)
            $etapaByTipoCultivo = \App\Models\EtapaFenologicaTipoCultivo::whereIn('tipo_cultivo_id', $tipoCultivoIds)
                ->orderBy('id')
                ->get(['tipo_cultivo_id', 'etapa_fenologica_id'])
                ->groupBy('tipo_cultivo_id')
                ->map(fn($rows) => optional($rows->first())->etapa_fenologica_id);

            // Mapa parcela_id => grupo_id desde grupo_parcela (para forzar el grupo del pivot)
            $grupoByParcela = $parcelas
                ->filter(fn ($gp) => !empty($gp->parcela_id))
                ->keyBy('parcela_id');

            $zonasManejo = $zonas->map(function ($z) use ($user, $grupoByParcela, $etapaByTipoCultivo) {
                $pivot = new GrupoZonaManejo();

                $pivot->user_id = $user->id;
                $pivot->cliente_id = $z->cliente_id;

                $gp = $grupoByParcela->get($z->parcela_id);
                $pivot->grupo_id = $gp ? $gp->grupo_id : $z->grupo_id;
                $pivot->cliente_id = $z->parcelaRel->cliente_id;
                
                // aunque no esté en $fillable, se puede asignar como atributo
                $pivot->parcela_id = $z->parcela_id;

                $pivot->zona_manejo_id = $z->id;
                $pivot->nombre = $z->nombre;

                // ✅ tipo_cultivo_id y etapa_fenologica_id calculados
                $tipoCultivoId = optional($z->tipoCultivos->first())->id;
                $pivot->tipo_cultivo_id = $tipoCultivoId ?: null;
                $pivot->etapa_fenologica_id = $tipoCultivoId ? ($etapaByTipoCultivo->get($tipoCultivoId) ?? null) : null;

                return $pivot;
            });

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
                $pivot->nombre = $z->nombre;

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
                $pivot->tipo_cultivo_id = $z->tipo_cultivo_id ?? null;
                $pivot->etapa_fenologica_id = $z->etapa_fenologica_id ?? null;

                return $pivot;
            })->unique(fn($i) => ($i->grupo_id ?? '0').'|'.($i->parcela_id ?? '0').'|'.$i->zona_manejo_id);

            //dd($zonasManejo)->values();
        }

        // Obtener los datos de Icamex para las zonas de manejo en un adición al objeto
        $zonasManejo->each(function ($zona) {
            $zona->icamex_data = $this->getDatosIcamex($zona->zona_manejo_id);
        });

        $data = [
            "section_name" => "Mis Zonas de Manejo",
            "section_description" => "Seleccione una zona de manejo para ver su información completa",
            "zonasManejo" => $zonasManejo,
            "parcelas" => $parcelas,
            "gruposRaiz" => $gruposRaiz,
            "user" => $user
         ];

        return view('grupos.zonas-manejo', $data);
    }

    private function getDatosIcamex($zona_id): array
    {
        $zonaManejoLote = \App\Models\ZonaManejoLoteIcamex::where('zona_manejo_id', $zona_id)->first();

        if (!$zonaManejoLote) {
            return [];
        }

        $loteId = $zonaManejoLote->icamex_lote_id; // <-- usa el valor real de la pivote

        $query = "
            SELECT *
            FROM dbo.lote l
            LEFT JOIN dbo.lote_elemento_icp lei ON l.id_lote = lei.id_lote
            LEFT JOIN dbo.lote_icp li ON l.id_lote = li.id_lote
            LEFT JOIN dbo.lote_indicador_icp lii ON l.id_lote = lii.id_lote
            LEFT JOIN dbo.lote_seccion_icp lsi ON l.id_lote = lsi.id_lote
            WHERE l.id_lote = ?
        ";

        $rows = DB::connection('external')->select($query, [$loteId]);

        return array_map(fn($r) => (array)$r, $rows);
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

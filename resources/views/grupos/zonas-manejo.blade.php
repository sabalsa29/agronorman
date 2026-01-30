@extends('layouts.web')
@section('title', $section_name)
@section('ruta_home', route('grupos.index'))

@section('content')
<div class="card">
    <div class="card-header header-elements-inline">
        <h5 class="card-title">{{ $section_name }}</h5>
        <div class="header-elements">
            <a href="{{ route('grupos.index') }}" class="btn btn-light btn-sm">
                <i class="icon-arrow-left7 mr-2"></i> Volver a Grupos
            </a>
            <div class="list-icons">
                <a class="list-icons-item" data-action="collapse"></a>
                <a class="list-icons-item" data-action="reload"></a>
                <a class="list-icons-item" data-action="remove"></a>
            </div>
        </div>
    </div>

    <div class="card-body">
        <p class="mb-4">{{ $section_description }}</p>

        {{-- ✅ Buscador --}}
        <div class="card border-top mb-4">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="card-title mb-0">
                    <i class="icon-search4 mr-2"></i>
                    <strong>Buscador</strong>
                </h6>
            </div>
            <div class="card-body">
                <div class="form-group mb-0">
                    <div class="input-group">
                        <input type="text" id="treeSearch" class="form-control"
                               placeholder="Buscar por grupo, subgrupo, parcela o zona...">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-primary" id="btnClearSearch">
                                <i class="icon-cross2"></i> Limpiar
                            </button>
                        </div>
                    </div>
                    <small class="form-text text-muted mt-2">
                        Escribe para filtrar y mostrar coincidencias dentro del árbol.
                    </small>
                </div>
            </div>
        </div>

        {{-- Cuadro para mostrar los datos de icamex de las zonas cargadas  --}}
        <div class="card border-top mb-4">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="card-title mb-0">
                    <i class="icon-database mr-2"></i>
                    <strong>Datos Icamex de Zonas de Manejo</strong>
                </h6>
            </div>
            <div class="card-body">
                @if(isset($zonasManejo) && $zonasManejo->isNotEmpty())
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Zona de Manejo</th>
                                <th>Datos Icamex</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($zonasManejo as $zona)
                                <tr>
                                    <td>{{ $zona->nombre }}</td>
                                    <td>
                                        @if(isset($zona->icamex_data) && !empty($zona->icamex_data))
                                            <pre>{{ print_r($zona->icamex_data[0], true) }}</pre>
                                        @else
                                            <em>No hay datos Icamex disponibles.</em>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="alert alert-info">
                        No hay zonas de manejo disponibles para mostrar datos Icamex.
                    </div>
                @endif
            </div>
        </div>

        @php
            use App\Models\Grupos;
            use App\Models\ZonaManejos;
            use App\Models\Parcelas;

            // -----------------------------------------
            // 1) Normalizar inputs del controller
            // -----------------------------------------
            $rootGroups = collect($gruposRaiz ?? [])
                ->flatten(1)     // por si viene array de colecciones
                ->filter()
                ->unique('id')
                ->values();

            $parcelasCol = collect($parcelas ?? [])
                ->flatten(1)
                ->filter()
                ->values(); // filas pivot GrupoParcela (grupo_id, parcela_id, ...)

            $zonasCol = collect($zonasManejo ?? [])
                ->flatten(1)
                ->filter()
                ->values(); // filas pivot GrupoZonaManejo (grupo_id, zona_manejo_id, [parcela_id opcional])

            // -----------------------------------------
            // 2) Sembrar también grupos que aparezcan en pivots
            //    (para que el árbol incluya grupos con parcelas/zona aunque no vengan en user_grupo)
            // -----------------------------------------
            $extraGroupIds = collect()
                ->merge($parcelasCol->pluck('grupo_id'))
                ->merge($zonasCol->pluck('grupo_id'))
                ->filter()
                ->map(fn($x) => (int)$x)
                ->unique()
                ->values();

            if ($extraGroupIds->isNotEmpty()) {
                $extraGroups = Grupos::whereIn('id', $extraGroupIds)->get();
                $rootGroups = $rootGroups->merge($extraGroups)->unique('id')->values();
            }

            // Si sigue vacío, no hay árbol que construir
            // -----------------------------------------
            // 3) Construir TODOS los grupos descendientes (N niveles) desde roots
            // -----------------------------------------
            $allGroupsMap = $rootGroups->keyBy('id');

            $pending = $rootGroups
                ->pluck('id')
                ->filter()
                ->map(fn($x) => (int)$x)
                ->values();

            while ($pending->isNotEmpty()) {
                // hijos donde grupo_id es el padre
                $kids = Grupos::whereIn('grupo_id', $pending)->get();

                $newKids = $kids->reject(fn($g) => $allGroupsMap->has($g->id));

                foreach ($newKids as $g) {
                    $allGroupsMap->put($g->id, $g);
                }

                $pending = $newKids
                    ->pluck('id')
                    ->filter()
                    ->map(fn($x) => (int)$x)
                    ->values();
            }

            $allGroups = $allGroupsMap->values();

            // Parent => children (para N niveles)
            $childrenByParent = $allGroups->groupBy(fn($g) => (int)($g->grupo_id ?? 0));

            // -----------------------------------------
            // 4) Parcelas por grupo (SIEMPRE via pivot grupo_parcela)
            // -----------------------------------------
            $parcelasByGrupo = $parcelasCol->groupBy(fn($gp) => (int)($gp->grupo_id ?? 0));

            // Lookup de parcelas para nombre (1 query)
            $parcelaIds = $parcelasCol->pluck('parcela_id')->filter()->unique()->values();
            $parcelaById = $parcelaIds->isEmpty()
                ? collect()
                : Parcelas::whereIn('id', $parcelaIds)->get()->keyBy('id');

            // -----------------------------------------
            // 5) Zonas: lookup para nombre y para derivar parcela_id
            //    (porque grupo_zona_manejo NO tiene parcela_id normalmente)
            // -----------------------------------------
            $zonaIds = $zonasCol->pluck('zona_manejo_id')->filter()->unique()->values();
            $zonaById = $zonaIds->isEmpty()
                ? collect()
                : ZonaManejos::whereIn('id', $zonaIds)->get(['id','parcela_id','grupo_id','nombre'])->keyBy('id');

            // Agrupar zonas por parcela:
            // - si pivot trae parcela_id, usarlo
            // - si no, derivarlo desde zona_manejos.parcela_id
            $zonasByParcela = $zonasCol->groupBy(function($gz) use ($zonaById) {
                $pid = (int)($gz->parcela_id ?? 0);

                if (!$pid) {
                    $zid = (int)($gz->zona_manejo_id ?? 0);
                    $pid = (int)($zonaById->get($zid)->parcela_id ?? 0);
                }

                return $pid; // 0 => sin parcela derivable
            });

            // -----------------------------------------
            // Helpers de nombre
            // -----------------------------------------
            $nombreGrupo = fn($g) => $g->nombre ?? ('Grupo #'.($g->id ?? ''));

            $nombreParcela = function($grupoParcelaRow) use ($parcelaById) {
                $pid = (int) ($grupoParcelaRow->parcela_id ?? 0);
                $p = $parcelaById->get($pid);
                return $p?->nombre ?? ('Parcela ID: '.$pid);
            };

            $nombreZona = function($grupoZonaRow) use ($zonaById) {
                $zid = (int) ($grupoZonaRow->zona_manejo_id ?? 0);
                $z = $zonaById->get($zid);
                return $z?->nombre ?? ('Zona ID: '.$zid);
            };

            // -----------------------------------------
            // 6) Definir raíces reales del árbol
            //    (si el grupo padre no está en allGroups => es raíz visible)
            // -----------------------------------------
            $allIds = $allGroups->pluck('id')->map(fn($x)=>(int)$x)->flip();

            $visibleRoots = $rootGroups->filter(function($g) use ($allIds){
                $pid = (int)($g->grupo_id ?? 0);
                return $pid === 0 || !$allIds->has($pid);
            })->unique('id')->values();
        @endphp

        {{-- ✅ Árbol --}}
        <div id="treeRoot">
            @if($allGroups->isEmpty() || $visibleRoots->isEmpty())
                <div class="alert alert-info">
                    No hay grupos disponibles para mostrar.
                </div>
            @else
                @php
                    $renderGroup = function($group, $level = 0) use (
                        &$renderGroup,
                        $childrenByParent,
                        $parcelasByGrupo,
                        $zonasByParcela,
                        $nombreGrupo,
                        $nombreParcela,
                        $nombreZona
                    ) {
                        $gid = (int)($group->id ?? 0);

                        $children = collect($childrenByParent->get($gid, []));
                        $parcelas = collect($parcelasByGrupo->get($gid, []));

                        $hasAny = $children->isNotEmpty() || $parcelas->isNotEmpty();

                        $pad = 12 + ($level * 14);

                        echo '<details class="tree-node tree-group" data-text="'.e($nombreGrupo($group)).'">';
                        echo '  <summary style="padding-left: '.$pad.'px;">';
                        echo '      <i class="icon-collaboration mr-2"></i>';
                        echo '      <strong>'.e($nombreGrupo($group)).'</strong>';
                        echo '      <span class="badge badge-light ml-2">'.(int)$children->count().' subgrupo(s)</span>';
                        echo '      <span class="badge badge-light ml-2">'.(int)$parcelas->count().' parcela(s)</span>';
                        echo '  </summary>';

                        if (!$hasAny) {
                            echo '<div class="tree-empty" style="padding-left: '.($pad+22).'px;">';
                            echo '  <span class="text-muted">Sin subgrupos ni parcelas.</span>';
                            echo '</div>';
                            echo '</details>';
                            return;
                        }

                        // ✅ Subgrupos (N niveles)
                        foreach ($children as $child) {
                            $renderGroup($child, $level + 1);
                        }

                        // ✅ Parcelas (via pivot grupo_parcela)
                        if ($parcelas->isNotEmpty()) {
                            echo '<div class="tree-branch">';

                            foreach ($parcelas as $gp) {
                                $parcelaId = (int)($gp->parcela_id ?? 0);
                                $zonas = $parcelaId ? collect($zonasByParcela->get($parcelaId, [])) : collect();

                                echo '<details class="tree-node tree-parcela" data-text="'.e($nombreParcela($gp)).'">';
                                echo '  <summary style="padding-left: '.($pad+18).'px;">';
                                echo '      <i class="icon-map5 mr-2"></i>';
                                echo '      <strong>'.e($nombreParcela($gp)).'</strong>';
                                echo '      <span class="badge badge-light ml-2">'.(int)$zonas->count().' zona(s)</span>';
                                echo '  </summary>';

                                if ($zonas->isEmpty()) {
                                    echo '<div class="tree-empty" style="padding-left: '.($pad+40).'px;">';
                                    echo '  <span class="text-muted">Sin zonas de manejo.</span>';
                                    echo '</div>';
                                } else {
                                    echo '<ul class="tree-list" style="padding-left: '.($pad+44).'px;">';

                                    foreach ($zonas as $gz) {
                                        //dd($gz);
                                        $zonaId = (int)($gz->zona_manejo_id ?? 0);

                                        $href = route('dashboard', array_filter([
                                            'cliente_id' => $gz->cliente_id,
                                            'parcela_id' => $parcelaId,
                                            'zona_manejo_id' => $zonaId,
                                            'tipo_cultivo_id' => $gz->tipo_cultivo_id,
                                            'etapa_fenologica_id' => $gz->etapa_fenologica_id,
                                            'periodo' => 1,
                                        ]));

                                        echo '<li class="tree-leaf tree-zona" data-text="'.e($nombreZona($gz)).'">';
                                        echo '  <a href="'.e($href).'" class="tree-link">';
                                        echo '      <i class="icon-location4 mr-2"></i>';
                                        echo        e($nombreZona($gz));
                                        echo '  </a>';
                                        echo '</li>';
                                    }

                                    echo '</ul>';
                                }

                                echo '</details>';
                            }

                            echo '</div>';
                        }

                        echo '</details>';
                    };
                @endphp

                @foreach($visibleRoots as $rg)
                    @php $renderGroup($rg, 0); @endphp
                @endforeach
            @endif
        </div>
    </div>
</div>

<style>
    .tree-node {
        border: 1px solid #e9ecef;
        border-radius: 10px;
        margin-bottom: 10px;
        background: #fff;
        overflow: hidden;
    }
    .tree-node > summary {
        list-style: none;
        cursor: pointer;
        padding: 10px 12px;
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        user-select: none;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
    }
    .tree-node[open] > summary {
        background: #eef2ff;
    }
    .tree-node > summary::-webkit-details-marker { display: none; }

    .tree-empty { padding: 10px 12px; }

    .tree-list {
        margin: 0;
        padding-top: 10px;
        padding-bottom: 10px;
    }
    .tree-leaf {
        list-style: none;
        margin: 6px 0;
    }
    .tree-link {
        display: inline-flex;
        align-items: center;
        padding: 8px 10px;
        border-radius: 8px;
        text-decoration: none !important;
        border: 1px solid transparent;
        width: fit-content;
        max-width: 100%;
    }
    .tree-link:hover {
        background: #f8f9fa;
        border-color: #e9ecef;
    }

    /* resaltado por búsqueda */
    .tree-hidden { display: none !important; }
    .tree-hit > summary,
    .tree-hit .tree-link {
        background: #fff3cd !important;
        border-color: #ffeeba !important;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const search = document.getElementById('treeSearch');
    const clear = document.getElementById('btnClearSearch');
    const root = document.getElementById('treeRoot');

    function normalize(s) {
        return (s || '')
            .toString()
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function filterTree(termRaw) {
        const term = normalize(termRaw);

        root.querySelectorAll('.tree-hit').forEach(el => el.classList.remove('tree-hit'));
        root.querySelectorAll('.tree-hidden').forEach(el => el.classList.remove('tree-hidden'));

        if (!term) return;

        const allNodes = Array.from(root.querySelectorAll('.tree-node, .tree-leaf'));

        allNodes.forEach(node => {
            const text = normalize(node.getAttribute('data-text'));
            const hit = text.includes(term);

            if (hit) {
                node.classList.add('tree-hit');

                // abrir padres
                let parent = node.parentElement;
                while (parent && parent !== root) {
                    if (parent.tagName === 'DETAILS') parent.open = true;
                    parent = parent.parentElement;
                }
            }
        });

        // ocultar details sin hit en descendientes ni self
        const allDetails = Array.from(root.querySelectorAll('details.tree-node'));
        allDetails.reverse().forEach(details => {
            const hasHitInside = !!details.querySelector('.tree-hit');
            const selfHit = details.classList.contains('tree-hit');
            if (!hasHitInside && !selfHit) details.classList.add('tree-hidden');
        });

        // ocultar hojas sin hit
        const leaves = Array.from(root.querySelectorAll('.tree-leaf'));
        leaves.forEach(li => {
            if (!li.classList.contains('tree-hit')) li.classList.add('tree-hidden');
        });
    }

    search.addEventListener('input', () => filterTree(search.value));
    clear.addEventListener('click', () => {
        search.value = '';
        filterTree('');
    });
});
</script>
@endsection

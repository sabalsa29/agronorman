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

        {{-- ✅ Buscador (se mantiene) --}}
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

        @php
            use App\Models\Grupos;
            use App\Models\GrupoParcela;
            use App\Models\GrupoZonaManejo;

            // AJUSTA estos modelos si en tu proyecto tienen otro nombre:
            // - Parcela model (tabla parcelas/predios)
            // - ZonaManejos model (tabla zona_manejos)
            use App\Models\Parcelas;        // <- ajusta si tu modelo se llama Predio o ParcelaModel
            use App\Models\ZonaManejos;    // <- ajusta si tu modelo se llama ZonaManejo

            // ---------------------------
            // 1) Grupos raíz (vienen del controller)
            // ---------------------------
            $rootGroups = collect($gruposRaiz ?? [])
                ->flatten(1)
                ->filter()
                ->values();

            // ---------------------------
            // 2) Traer TODOS los descendientes (N niveles) desde esas raíces
            // ---------------------------
            $allGroupsMap = $rootGroups->keyBy('id');
            $pending = $rootGroups->pluck('id')->map(fn($x) => (int)$x)->values();

            while ($pending->isNotEmpty()) {
                $kids = Grupos::whereIn('grupo_id', $pending)->get();
                $newKids = $kids->reject(fn($g) => $allGroupsMap->has($g->id));

                foreach ($newKids as $g) {
                    $allGroupsMap->put($g->id, $g);
                }

                $pending = $newKids->pluck('id')->map(fn($x) => (int)$x)->values();
            }

            $allGroups = $allGroupsMap->values();

            // Parent -> children (para TODOS los niveles)
            $childrenByParent = $allGroups->groupBy(fn($g) => (int)($g->grupo_id ?? 0));

            // ---------------------------
            // 3) Parcelas por grupo usando SOLO la pivot grupo_parcela
            //    $parcelas aquí son REGISTROS de GrupoParcela (grupo_id, parcela_id)
            // ---------------------------
            $parcelasCol = collect($parcelas ?? []);
            $parcelasByGrupo = $parcelasCol->groupBy(fn($gp) => (int)($gp->grupo_id ?? 0));

            // Lookup de parcelas (para mostrar nombres) - 1 sola consulta
            $parcelaIds = $parcelasCol->pluck('parcela_id')->filter()->unique()->values();
            $parcelaById = $parcelaIds->isEmpty()
                ? collect()
                : Parcelas::whereIn('id', $parcelaIds)->get()->keyBy('id');

            // ---------------------------
            // 4) Zonas por parcela usando pivot grupo_zona_manejo
            // ---------------------------
            $zonasCol = collect($zonasManejo ?? []);
            $zonasByParcela = $zonasCol->groupBy(fn($gz) => (int)($gz->parcela_id ?? 0));

            // Lookup de zonas (para mostrar nombres) - 1 sola consulta
            $zonaIds = $zonasCol->pluck('zona_manejo_id')->filter()->unique()->values();
            $zonaById = $zonaIds->isEmpty()
                ? collect()
                : ZonaManejos::whereIn('id', $zonaIds)->get()->keyBy('id');

            // Helpers de nombre (ahora usando lookups)
            $nombreGrupo = fn($g) => $g->nombre ?? ('Grupo #'.$g->id);

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
        @endphp

        {{-- ✅ Árbol --}}
        <div id="treeRoot">

            @if($allGroups->isEmpty())
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
                        $gid = (int) $group->id ?? 0;

                        // ✅ ahora sí existen hijos para N niveles
                        $children = collect($childrenByParent->get($gid, []));
                        $parcelas = collect($parcelasByGrupo->get($gid, []));
                        
                        //

                        $hasAny = $children->isNotEmpty() || $parcelas->isNotEmpty();

                        $pad = 12 + ($level * 14);

                        echo '<details class="tree-node tree-group" data-text="'.e($nombreGrupo($group)).'">';
                        echo '  <summary style="padding-left: '.$pad.'px;">';
                        echo '      <i class="icon-collaboration mr-2"></i>';
                        echo '      <strong>'.e($nombreGrupo($group)).'</strong>';
                        echo '      <span class="text-muted ml-2 small">(ID: '.$gid.')</span>';
                        echo '  </summary>';

                        if(!$hasAny) {
                            echo '<div class="tree-empty" style="padding-left: '.($pad+22).'px;">';
                            echo '  <span class="text-muted">Sin subgrupos ni parcelas.</span>';
                            echo '</div>';
                        }
                        //dd($parcelas);
                        // ✅ Subgrupos (N niveles)
                        foreach($children as $child) {
                            $renderGroup($child, $level + 1);
                        }

                        // ✅ Parcelas del grupo/subgrupo actual
                       
                        // ✅ Parcelas del grupo/subgrupo actual (VIA grupo_parcela pivot)
                        $parcelas = collect($parcelasByGrupo->get($gid, []));
                        //dd($parcelas);
                        if ($parcelas->isNotEmpty()) {
                            echo '<div class="tree-branch">';

                            foreach ($parcelas as $gp) {

                                // ✅ OJO: ESTA ES LA ID REAL DE LA PARCELA (no uses $gp->id)
                                $parcelaId = (int) ($gp->parcela_id ?? 0);

                                // ✅ Zonas por parcela (una parcela puede tener MUCHAS zonas)
                                $zonas = collect($zonasByParcela->get($parcelaId, []));

                                echo '<details class="tree-node tree-parcela" data-text="'.e($nombreParcela($gp)).'">';
                                echo '  <summary style="padding-left: '.($pad+18).'px;">';
                                echo '      <i class="icon-map5 mr-2"></i>';
                                echo '      <strong>'.e($nombreParcela($gp)).'</strong>';
                                echo '      <span class="text-muted ml-2 small">(Parcela ID: '.$parcelaId.')</span>';
                                echo '      <span class="badge badge-light ml-2">'.(int)$zonas->count().' zona(s)</span>';
                                echo '  </summary>';

                                if ($zonas->isEmpty()) {
                                    echo '<div class="tree-empty" style="padding-left: '.($pad+40).'px;">';
                                    echo '  <span class="text-muted">Sin zonas de manejo.</span>';
                                    echo '</div>';
                                } else {
                                    echo '<ul class="tree-list" style="padding-left: '.($pad+44).'px;">';

                                    foreach ($zonas as $gz) {
                                        $zonaId = (int) ($gz->zona_manejo_id ?? 0);

                                        // Link (ajusta los params a tu dashboard real si aplica)
                                        $href = route('grupos.zonas-manejo', array_filter([
                                            'parcela_id' => $parcelaId,
                                            'zona_manejo_id' => $zonaId,
                                            'periodo' => 1,
                                        ]));

                                        echo '<li class="tree-leaf tree-zona" data-text="'.e($nombreZona($gz)).'">';
                                        echo '  <a href="'.e($href).'" class="tree-link">';
                                        echo '      <i class="icon-location4 mr-2"></i>'.e($nombreZona($gz));
                                        echo '      <span class="text-muted ml-2 small">(Zona ID: '.$zonaId.')</span>';
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

                @foreach($rootGroups as $rg)
                    @php $renderGroup($rg, 0); @endphp
                @endforeach
            @endif

        </div>
    </div>
</div>

<style>
    /* árbol base */
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
    }
    .tree-node[open] > summary {
        background: #eef2ff;
    }
    .tree-node > summary::-webkit-details-marker { display: none; }
    .tree-empty {
        padding: 10px 12px;
    }
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

        // limpiar
        root.querySelectorAll('.tree-hit').forEach(el => el.classList.remove('tree-hit'));
        root.querySelectorAll('.tree-hidden').forEach(el => el.classList.remove('tree-hidden'));

        if (!term) {
            // si no hay búsqueda, no ocultar nada
            return;
        }

        // recorremos todos los nodos (details y leaves)
        const allNodes = Array.from(root.querySelectorAll('.tree-node, .tree-leaf'));

        allNodes.forEach(node => {
            const text = normalize(node.getAttribute('data-text'));
            const hit = text.includes(term);

            if (hit) {
                node.classList.add('tree-hit');

                // abrir todos sus padres details
                let parent = node.parentElement;
                while (parent && parent !== root) {
                    if (parent.tagName === 'DETAILS') parent.open = true;
                    parent = parent.parentElement;
                }
            }
        });

        // ocultar nodos que NO tengan coincidencia en sí o en descendientes
        const allDetails = Array.from(root.querySelectorAll('details.tree-node'));
        allDetails.reverse().forEach(details => {
            const hasHitInside = !!details.querySelector('.tree-hit');
            const selfHit = details.classList.contains('tree-hit');
            if (!hasHitInside && !selfHit) {
                details.classList.add('tree-hidden');
            }
        });

        // hojas
        const leaves = Array.from(root.querySelectorAll('.tree-leaf'));
        leaves.forEach(li => {
            if (!li.classList.contains('tree-hit')) {
                li.classList.add('tree-hidden');
            }
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

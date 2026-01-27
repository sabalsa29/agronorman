@extends('layouts.web')
@section('title', $section_name)
@section('ruta_home', route('grupos.index'))

@section('content')
@php
    // Normalizar variables que SÍ mandas desde el controller
    $gruposRaiz  = collect($grupoUsuario ?? $gruposRaiz ?? []);
    $parcelasAll = collect($parcelas ?? []);
    $zonasAll    = collect($zonasManejo ?? []);
    $busqueda    = request('busqueda', '');

    // Si $gruposRaiz viene como array de collections (por el foreach), lo aplanamos
    $gruposRaiz = $gruposRaiz->flatten(1)->filter()->values();

    $grupoSeleccionadoId  = (int) request('grupo_raiz_id', 0);
    $parcelaSeleccionadaId = (int) request('parcela_id', 0);

    // Parcelas por grupo (se asume GrupoParcela: grupo_id, parcela_id y/o relación parcela->nombre)
    $parcelasPorGrupo = $parcelasAll->groupBy(fn($p) => (int) data_get($p, 'grupo_id', 0));

    $parcelasDelGrupo = $grupoSeleccionadoId
        ? collect($parcelasPorGrupo->get($grupoSeleccionadoId, collect()))->values()
        : collect();

    // helper para nombre parcela
    $nombreParcela = function($p) {
        $id = (int) data_get($p, 'parcela_id', data_get($p, 'id', 0));
        return data_get($p, 'parcela.nombre')
            ?? data_get($p, 'predio.nombre')
            ?? data_get($p, 'nombre')
            ?? ('Parcela #'.$id);
    };
@endphp

<div class="card">
    <div class="card-header header-elements-inline">
        <h5 class="card-title">{{ $section_name }}</h5>
        <div class="header-elements">
            <a href="{{ route('grupos.index') }}" class="btn btn-light btn-sm">
                <i class="icon-arrow-left7 mr-2"></i> Volver a Grupos
            </a>
        </div>
    </div>

    <div class="card-body">
        <p class="mb-4">{{ $section_description }}</p>

        {{-- =========================
             SELECT DE GRUPOS RAÍZ
           ========================= --}}
        <div class="card border-top mb-4">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="card-title mb-0">
                    <i class="icon-collaboration mr-2"></i>
                    <strong>Filtros</strong>
                </h6>
            </div>

            <div class="card-body">
                <form method="GET" action="{{ route('grupos.zonas-manejo') }}" id="formFiltros">
                    <div class="row">
                       <div class="col-md-6 mb-3">
                        <label class="font-weight-semibold">
                            <i class="icon-filter4 mr-1"></i> Grupos raíz
                        </label>

                        <select name="grupo_raiz_id" id="grupo_raiz_id" class="form-control">
                            <option value="">-- Selecciona un grupo --</option>
                            @foreach ($gruposRaiz as $g)
                                <option value="{{ $g->id }}" {{ (int)$g->id === (int)$grupoSeleccionadoId ? 'selected' : '' }}>
                                    {{ $g->nombre }} (ID: {{ $g->id }})
                                </option>
                            @endforeach
                        </select>

                        <small class="form-text text-muted mt-1">
                            Selecciona un grupo: primero buscamos subgrupos; si no hay, pedimos parcela.
                        </small>
                    </div>

                    {{-- Aquí se irán agregando selects de subgrupos en cascada --}}
                    <div class="col-md-6 mb-3" id="subgruposContainer" style="display:none;"></div>

                    {{-- Select de parcelas (solo cuando ya no hay subgrupos) --}}
                    <div class="col-md-6 mb-3" id="parcelasContainer" style="display:none;">
                        <label class="font-weight-semibold">
                            <i class="icon-map5 mr-1"></i> Parcela
                        </label>
                        <select name="parcela_id" id="parcela_id" class="form-control">
                            <option value="">-- Selecciona una parcela --</option>
                        </select>
                        <small class="form-text text-muted mt-1">
                            Selecciona una parcela para filtrar zonas.
                        </small>
                    </div>

                    {{-- opcional: para que el backend sepa cuál fue el ÚLTIMO grupo/subgrupo seleccionado --}}
                    <input type="hidden" name="grupo_leaf_id" id="grupo_leaf_id" value="{{ (int)request('grupo_leaf_id', 0) }}">

                    </div>

                    {{-- mantener busqueda en los filtros --}}
                    @if($busqueda)
                        <input type="hidden" name="busqueda" value="{{ $busqueda }}">
                    @endif
                </form>
            </div>
        </div>

        {{-- =========================
             BUSCADOR (se mantiene como pediste)
           ========================= --}}
        <div class="card border-top mb-4">
            <div class="card-header bg-transparent border-bottom">
                <h6 class="card-title mb-0">
                    <i class="icon-search4 mr-2"></i>
                    <strong>Buscador de Zonas</strong>
                </h6>
            </div>

            <div class="card-body">
                <form method="GET" action="{{ route('grupos.zonas-manejo') }}" id="formBusqueda">
                    @if ($grupoSeleccionadoId)
                        <input type="hidden" name="grupo_raiz_id" value="{{ $grupoSeleccionadoId }}">
                    @endif

                    @if ($parcelaSeleccionadaId)
                        <input type="hidden" name="parcela_id" value="{{ $parcelaSeleccionadaId }}">
                    @endif

                    <div class="form-group mb-0">
                        <div class="input-group">
                            <input type="text" name="busqueda" id="busqueda" class="form-control"
                                   value="{{ $busqueda }}"
                                   placeholder="Buscar por nombre de zona..."
                                   onkeyup="if(event.key === 'Enter') document.getElementById('formBusqueda').submit();">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">
                                    <i class="icon-search4 mr-1"></i> Buscar
                                </button>

                                @if ($busqueda)
                                    <a href="{{ route('grupos.zonas-manejo', array_filter([
                                            'grupo_raiz_id' => $grupoSeleccionadoId ?: null,
                                            'parcela_id' => $parcelaSeleccionadaId ?: null,
                                        ])) }}"
                                       class="btn btn-light">
                                        <i class="icon-cross2"></i> Limpiar
                                    </a>
                                @endif
                            </div>
                        </div>
                        <small class="form-text text-muted mt-2">
                            Busca por nombre de zona (luego lo extendemos a grupo/parcela si quieres).
                        </small>
                    </div>
                </form>
            </div>
        </div>

        {{-- =========================
             LISTADO ACTUAL (temporal)
             (tu controller aún manda GrupoZonaManejo, no datos armados de zona/cliente/cultivo)
           ========================= --}}
        @php
            // Filtrado de UI (solo para mostrar algo coherente mientras ajustamos controller)
            $zonasFiltradas = $zonasAll;

            if ($grupoSeleccionadoId) {
                $zonasFiltradas = $zonasFiltradas->where('grupo_id', $grupoSeleccionadoId);
            }
            if ($parcelaSeleccionadaId) {
                $zonasFiltradas = $zonasFiltradas->where('parcela_id', $parcelaSeleccionadaId);
            }
            if ($busqueda) {
                $t = strtolower(trim($busqueda));
                $zonasFiltradas = $zonasFiltradas->filter(function($z) use ($t) {
                    $nombre = strtolower((string)(
                        data_get($z, 'zonaManejo.nombre')
                        ?? data_get($z, 'zona_manejo.nombre')
                        ?? data_get($z, 'nombre')
                        ?? ''
                    ));
                    return str_contains($nombre, $t);
                });
            }

            $zonasFiltradas = $zonasFiltradas->values();
        @endphp

        @if ($busqueda)
            <div class="alert alert-info mb-4" role="alert">
                <strong>{{ $zonasFiltradas->count() }}</strong>
                zona{{ $zonasFiltradas->count() != 1 ? 's' : '' }}
                encontrada{{ $zonasFiltradas->count() != 1 ? 's' : '' }}
                para "<strong>{{ $busqueda }}</strong>"
            </div>
        @endif

        @if ($zonasFiltradas->isEmpty())
            <div class="alert alert-info">
                No hay zonas para mostrar con los filtros actuales.
            </div>
        @else
            <div class="list-group">
                @foreach ($zonasFiltradas as $z)
                    @php
                        $zonaId = (int) data_get($z, 'zona_manejo_id', data_get($z, 'id', 0));
                        $nombreZona =
                            data_get($z, 'zonaManejo.nombre')
                            ?? data_get($z, 'zona_manejo.nombre')
                            ?? data_get($z, 'nombre')
                            ?? ('Zona #'.$zonaId);

                        $pid = (int) data_get($z, 'parcela_id', 0);
                        $gid = (int) data_get($z, 'grupo_id', 0);
                    @endphp

                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $nombreZona }}</strong>
                            <div class="text-muted small">
                                Grupo ID: {{ $gid }} | Parcela ID: {{ $pid }} | Zona ID: {{ $zonaId }}
                            </div>
                        </div>
                        <a class="btn btn-sm btn-primary"
                           href="{{ route('grupos.zonas-manejo', array_filter([
                                'grupo_raiz_id' => $grupoSeleccionadoId ?: null,
                                'parcela_id' => $pid ?: null,
                                'zona_manejo_id' => $zonaId ?: null,
                                'periodo' => 1,
                           ])) }}">
                            <i class="icon-arrow-right8 mr-1"></i> Dashboard
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>
@endsection
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formFiltros');
    const grupoSelect = document.getElementById('grupo_raiz_id');

    const subgruposContainer = document.getElementById('subgruposContainer');
    const parcelasContainer = document.getElementById('parcelasContainer');
    const parcelaSelect = document.getElementById('parcela_id');

    const grupoLeafInput = document.getElementById('grupo_leaf_id');

    // Endpoints (crea estas rutas en Laravel como te dejo más abajo)
    const urlSubgrupos = (grupoId) => `{{ url('/grupos') }}/${grupoId}/subgrupos-json`;
    const urlParcelas  = (grupoId) => `{{ url('/grupos') }}/${grupoId}/parcelas-json`;

    function resetSubgrupos() {
        subgruposContainer.innerHTML = '';
        subgruposContainer.style.display = 'none';
    }

    function resetParcelas() {
        parcelaSelect.innerHTML = `<option value="">-- Selecciona una parcela --</option>`;
        parcelasContainer.style.display = 'none';
    }

    function removeDeeperSubgroupSelects(fromLevel) {
        const selects = subgruposContainer.querySelectorAll('[data-level]');
        selects.forEach(sel => {
            const level = parseInt(sel.getAttribute('data-level'), 10);
            if (level >= fromLevel) sel.closest('.subgrupo-level').remove();
        });
    }

    async function fetchJSON(url) {
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error(`Error ${res.status} al consultar ${url}`);
        return res.json();
    }

    function renderSubgroupSelect(level, items, parentSelectedId) {
        subgruposContainer.style.display = 'block';

        const wrapper = document.createElement('div');
        wrapper.className = 'subgrupo-level mb-2';

        const label = document.createElement('label');
        label.className = 'font-weight-semibold';
        label.innerHTML = `<i class="icon-collaboration mr-1"></i> Subgrupo (nivel ${level + 1})`;

        const select = document.createElement('select');
        select.className = 'form-control';
        select.setAttribute('data-level', level);

        select.innerHTML = `<option value="">-- Selecciona un subgrupo --</option>` +
            items.map(g => `<option value="${g.id}">${g.nombre} (ID: ${g.id})</option>`).join('');

        select.addEventListener('change', async (e) => {
            const selectedId = parseInt(e.target.value || '0', 10);

            // borrar selects de niveles más profundos
            removeDeeperSubgroupSelects(level + 1);
            resetParcelas();

            // Si el usuario deselecciona, vuelve a intentar con el grupo padre de este nivel
            if (!selectedId) {
                grupoLeafInput.value = parentSelectedId || '';
                return;
            }

            // actualizamos leaf
            grupoLeafInput.value = selectedId;

            // consultar si ese subgrupo tiene hijos
            await loadNext(selectedId, level + 1);
        });

        wrapper.appendChild(label);
        wrapper.appendChild(select);
        subgruposContainer.appendChild(wrapper);
    }

    async function loadParcelas(grupoId) {
        resetParcelas();

        const data = await fetchJSON(urlParcelas(grupoId));
        const parcelas = (data.parcelas || []);

        if (!parcelas.length) {
            // si no hay parcelas, mostramos un mensaje
            parcelasContainer.style.display = 'block';
            parcelaSelect.innerHTML = `<option value="">-- No hay parcelas disponibles --</option>`;
            return;
        }

        parcelaSelect.innerHTML = `<option value="">-- Selecciona una parcela --</option>` +
            parcelas.map(p => `<option value="${p.id}">${p.nombre} (ID: ${p.id})</option>`).join('');

        parcelasContainer.style.display = 'block';
    }

    async function loadNext(grupoId, level) {
        const data = await fetchJSON(urlSubgrupos(grupoId));
        const hijos = (data.subgrupos || []);

        if (hijos.length > 0) {
            // hay subgrupos -> render select de este nivel
            renderSubgroupSelect(level, hijos, grupoId);
        } else {
            // NO hay subgrupos -> ahora sí pedir parcelas
            await loadParcelas(grupoId);
        }
    }

    // Cuando cambie el grupo raíz:
    grupoSelect.addEventListener('change', async (e) => {
        const grupoId = parseInt(e.target.value || '0', 10);

        resetSubgrupos();
        resetParcelas();

        if (!grupoId) {
            grupoLeafInput.value = '';
            return;
        }

        // leaf inicial = grupo seleccionado
        grupoLeafInput.value = grupoId;

        try {
            await loadNext(grupoId, 0);
        } catch (err) {
            console.error(err);
            resetSubgrupos();
            resetParcelas();
        }
    });

    // Cuando se elige parcela -> submit del form para aplicar filtro
    parcelaSelect.addEventListener('change', () => {
        form.submit();
    });

    // Si quieres: si ya viene grupo seleccionado en la URL, auto-cargar
    const preGrupo = parseInt(grupoSelect.value || '0', 10);
    if (preGrupo) {
        grupoLeafInput.value = preGrupo;
        loadNext(preGrupo, 0).catch(console.error);
    }
});
</script>
@endsection

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

            @if ($grupoUsuario)
                {{-- Filtros de Grupos - Diseño Mejorado --}}
                <div class="card border-top mb-4">
                    <div class="card-header bg-transparent border-bottom">
                        <h6 class="card-title mb-0">
                            <i class="icon-collaboration mr-2"></i>
                            <strong>Jerarquía de Grupos</strong>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            {{-- Mostrar todos los ancestros (hacia arriba) como campos fijos --}}
                            @foreach ($gruposAncestros as $ancestro)
                                <div class="col-md-3 mb-3">
                                    <div class="form-group mb-0">
                                        <label class="font-weight-semibold text-muted small">
                                            Grupo Ancestro
                                        </label>
                                        <input type="text" class="form-control" value="{{ $ancestro->nombre }}" readonly
                                            style="background-color: #f8f9fa; cursor: default;"
                                            title="Campo informativo - muestra la jerarquía de grupos">
                                    </div>
                                </div>
                            @endforeach

                            {{-- Grupo del Usuario (fijo, inamovible) --}}
                            <div class="col-md-3 mb-3">
                                <div class="form-group mb-0">
                                    <label class="font-weight-semibold">
                                        Mi Grupo
                                    </label>
                                    <input type="text" class="form-control" value="{{ $grupoUsuario->nombre }}" readonly
                                        style="background-color: #e9ecef; cursor: default; font-weight: 500;"
                                        title="Tu grupo asignado - no se puede cambiar">
                                </div>
                            </div>

                            {{-- Filtro de Subgrupos (dinámico) - Solo descendientes del grupo del usuario --}}
                            @if ($subgrupos->count() > 0)
                                <div class="col-md-{{ $gruposAncestros->count() > 0 ? 3 : 4 }} mb-3">
                                    <form method="GET" action="{{ route('grupos.zonas-manejo') }}" id="filtroSubgrupo">
                                        @if ($user->isSuperAdmin())
                                            <input type="hidden" name="grupo_raiz_id" value="{{ $grupoUsuario->id }}">
                                        @endif
                                        @if ($busqueda)
                                            <input type="hidden" name="busqueda" value="{{ $busqueda }}">
                                        @endif
                                        <div class="form-group mb-0">
                                            <label for="subgrupo_id" class="font-weight-semibold">
                                                <i class="icon-filter4 mr-1"></i>
                                                Filtrar por Subgrupo
                                            </label>
                                            <select name="subgrupo_id" id="subgrupo_id" class="form-control"
                                                onchange="document.getElementById('filtroSubgrupo').submit();"
                                                style="cursor: pointer;">
                                                <option value="">
                                                    📍 {{ $grupoUsuario->nombre }} — Todas las zonas
                                                </option>
                                                @foreach ($subgrupos as $subgrupo)
                                                    @include('grupos.partials.subgrupo-option', [
                                                        'subgrupo' => $subgrupo,
                                                        'subgrupoFiltro' => $subgrupoFiltro,
                                                    ])
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted mt-1">
                                                <i class="icon-info22 mr-1"></i>
                                                Selecciona un subgrupo para ver solo sus zonas
                                            </small>
                                        </div>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Buscador de Zonas de Manejo - Diseño Mejorado --}}
            <div class="card border-top mb-4">
                <div class="card-header bg-transparent border-bottom">
                    <h6 class="card-title mb-0">
                        <i class="icon-search4 mr-2"></i>
                        <strong>Buscador de Zonas</strong>
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('grupos.zonas-manejo') }}" id="formBusqueda">
                        @if ($user->isSuperAdmin() && $grupoUsuario)
                            <input type="hidden" name="grupo_raiz_id" value="{{ $grupoUsuario->id }}">
                        @endif
                        @if ($subgrupoFiltro)
                            <input type="hidden" name="subgrupo_id" value="{{ $subgrupoFiltro }}">
                        @endif
                        <div class="form-group mb-0">
                            <div class="input-group">
                                <input type="text" name="busqueda" id="busqueda" class="form-control"
                                    value="{{ $busqueda }}"
                                    placeholder="Buscar por nombre, cliente, parcela, cultivo o grupo..."
                                    onkeyup="if(event.key === 'Enter') document.getElementById('formBusqueda').submit();">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="icon-search4 mr-1"></i> Buscar
                                    </button>
                                    @if ($busqueda)
                                        <a href="{{ route(
                                            'grupos.zonas-manejo',
                                            array_filter([
                                                'grupo_raiz_id' => $user->isSuperAdmin() && $grupoUsuario ? $grupoUsuario->id : null,
                                                'subgrupo_id' => $subgrupoFiltro,
                                            ]),
                                        ) }}"
                                            class="btn btn-light">
                                            <i class="icon-cross2"></i> Limpiar
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <small class="form-text text-muted mt-2">
                                Busca por nombre de zona, cliente, parcela, tipo de cultivo o grupo
                            </small>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Contador de resultados - Diseño Mejorado --}}
            @if ($busqueda)
                <div class="alert alert-info mb-4" role="alert">
                    <strong>{{ $zonasManejo->count() }}</strong>
                    zona{{ $zonasManejo->count() != 1 ? 's' : '' }}
                    encontrada{{ $zonasManejo->count() != 1 ? 's' : '' }}
                    para "<strong>{{ $busqueda }}</strong>"
                </div>
            @endif

            @forelse($zonasManejo as $zona)
                <div class="card mb-4 zona-card shadow-sm border-0"
                    style="cursor: pointer; transition: all 0.3s ease; border-left: 4px solid #667eea !important;"
                    onclick="window.location.href='{{ route('grupos.zonas-manejo', [
                        'cliente_id' => $zona['cliente_id'],
                        'parcela_id' => $zona['parcela_id'],
                        'zona_manejo_id' => $zona['id'],
                        'tipo_cultivo_id' => $zona['tipo_cultivo_id'],
                        'etapa_fenologica_id' => $zona['etapa_fenologica_id'],
                        'periodo' => 1,
                    ]) }}'">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="mr-3">
                                        <div class="bg-light text-dark rounded-circle d-flex align-items-center justify-content-center border"
                                            style="width: 50px; height: 50px; font-size: 1.5rem;">
                                            <i class="icon-location4"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-0 font-weight-bold text-dark"
                                            style="font-size: 1.25rem;">
                                            {{ $zona['nombre'] }}
                                        </h5>
                                        @if ($zona['grupo'])
                                            <span class="badge badge-secondary badge-pill mt-1">
                                                <i class="icon-collaboration mr-1"></i>{{ $zona['grupo'] }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6 mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded p-2 mr-2 border">
                                                <i class="icon-office text-muted"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block"
                                                    style="font-size: 0.75rem; line-height: 1.2;">Cliente</small>
                                                <strong class="text-dark">{{ $zona['cliente'] }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded p-2 mr-2 border">
                                                <i class="icon-map5 text-muted"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block"
                                                    style="font-size: 0.75rem; line-height: 1.2;">Parcela</small>
                                                <strong class="text-dark">{{ $zona['parcela'] }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded p-2 mr-2 border">
                                                <i class="icon-leaf text-muted"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block"
                                                    style="font-size: 0.75rem; line-height: 1.2;">Cultivo</small>
                                                <strong class="text-dark">{{ $zona['tipo_cultivo_nombre'] }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right ml-3">
                                <div class="d-flex flex-column align-items-end">
                                    <button class="btn btn-primary" style="min-width: 140px;">
                                        <i class="icon-arrow-right8 mr-2"></i>
                                        Ver Dashboard
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-info">
                    <h5 class="alert-heading">
                        @if ($busqueda)
                            No se encontraron zonas de manejo
                        @else
                            No hay zonas de manejo disponibles
                        @endif
                    </h5>
                    <p class="mb-0">
                        @if ($busqueda)
                            No se encontraron zonas de manejo que coincidan con "<strong>{{ $busqueda }}</strong>".
                            <br>
                            <a href="{{ route(
                                'grupos.zonas-manejo',
                                array_filter([
                                    'grupo_raiz_id' => $user->isSuperAdmin() && $grupoUsuario ? $grupoUsuario->id : null,
                                    'subgrupo_id' => $subgrupoFiltro,
                                ]),
                            ) }}"
                                class="alert-link">Limpiar búsqueda</a> para ver todas las zonas disponibles.
                        @else
                            No se encontraron zonas de manejo con la información completa necesaria para mostrar el
                            dashboard.
                            Asegúrese de que las zonas de manejo tengan:
                            <ul class="mb-0 mt-2">
                                <li>Una parcela asignada</li>
                                <li>Un tipo de cultivo asociado</li>
                                <li>Una etapa fenológica configurada</li>
                            </ul>
                        @endif
                    </p>
                </div>
            @endforelse
        </div>
    </div>

    <style>
        .zona-card {
            border-left: 4px solid #dee2e6 !important;
            transition: all 0.3s ease;
            background: #ffffff;
        }

        .zona-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
            border-left-color: #6c757d !important;
        }

        .zona-card .card-body {
            padding: 1.5rem;
        }

        .zona-card h5 {
            color: #212529;
            font-weight: 600;
        }

        .zona-card .bg-light {
            background-color: #f8f9fa !important;
        }
    </style>
@endsection

@php
    $tieneSubgrupos = isset($grupo['subgrupos']) && count($grupo['subgrupos']) > 0;
    $tieneZonas = isset($grupo['zonas_manejo']) && count($grupo['zonas_manejo']) > 0;
    $tieneUsuarios = isset($grupo['usuarios']) && count($grupo['usuarios']) > 0;
    $grupoId = 'grupo-' . $grupo['id'];
    $subgruposId = 'subgrupos-' . $grupo['id'];
@endphp

<div class="grupo-item mb-2" data-grupo-id="{{ $grupo['id'] }}" data-nivel="{{ $nivel }}">
    <div class="card border-left-{{ $nivel == 0 ? 'primary' : 'info' }} border-left-3 shadow-sm">
        <div class="card-body py-2">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center mb-1">
                        {{-- Botón expandir/colapsar --}}
                        @if ($tieneSubgrupos)
                            <button type="button" class="btn btn-sm btn-link p-0 mr-1 toggle-subgrupos"
                                data-target="#{{ $subgruposId }}" data-grupo-id="{{ $grupo['id'] }}"
                                title="Expandir/Colapsar subgrupos">
                                <i class="icon-arrow-down8 toggle-icon" style="font-size: 14px;"></i>
                            </button>
                        @else
                            <span class="mr-2" style="width: 20px; display: inline-block;"></span>
                        @endif

                        <h6 class="mb-0 font-weight-semibold" style="font-size: 14px;">
                            <i class="icon-collaboration mr-1 text-{{ $nivel == 0 ? 'primary' : 'info' }}"></i>
                            {{ $grupo['nombre'] }}
                        </h6>
                        @if ($grupo['status'] == 1)
                            <span class="badge badge-success ml-2">Activo</span>
                        @else
                            <span class="badge badge-danger ml-2">Inactivo</span>
                        @endif
                    </div>

                    <div class="ml-4">
                        <div class="d-flex flex-wrap">
                            @if ($tieneSubgrupos)
                                <span class="badge badge-info mr-2 mb-1">
                                    <i class="icon-tree5 mr-1"></i>{{ count($grupo['subgrupos']) }} subgrupo(s)
                                </span>
                            @endif
                            @if ($tieneZonas)
                                <span class="badge badge-secondary mr-2 mb-1">
                                    <i class="icon-menu6 mr-1"></i>{{ count($grupo['zonas_manejo']) }} zona(s)
                                </span>
                            @endif
                            @if ($tieneUsuarios)
                                <span class="badge badge-warning mr-2 mb-1">
                                    <i class="icon-users mr-1"></i>{{ count($grupo['usuarios']) }} usuario(s)
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="actions-group ml-3 d-flex flex-wrap">
                    @if (Auth::check() && (Auth::user()->canCreate('estaciones.grupos') || Auth::user()->canCreate('usuarios.grupos')))
                        <a href="{{ route('grupos.create', ['grupo_padre_id' => $grupo['id']]) }}"
                            class="btn btn-sm btn-success mr-1 mb-1" title="Crear subgrupo">
                            <i class="icon-plus-circle2 mr-1"></i> Subgrupo
                        </a>
                    @endif
                    @if (Auth::check() && (Auth::user()->canEdit('estaciones.grupos') || Auth::user()->canEdit('usuarios.grupos')))
                        <a href="{{ route('grupos.edit', $grupo['id']) }}" class="btn btn-sm btn-primary mr-1 mb-1"
                            title="Editar">
                            <i class="icon-pencil7"></i>
                        </a>
                    @endif
                    @if (Auth::check() && (Auth::user()->canDelete('estaciones.grupos') || Auth::user()->canDelete('usuarios.grupos')))
                        <form action="{{ route('grupos.destroy', $grupo['id']) }}" method="POST"
                            class="d-inline delete-form mr-1 mb-1">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="btn btn-sm btn-danger delete-button"
                                data-name="{{ $grupo['nombre'] }}" title="Eliminar">
                                <i class="icon-trash"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if ($tieneSubgrupos)
    <div id="{{ $subgruposId }}" class="subgrupos-container ml-3" style="display: none;">
        @foreach ($grupo['subgrupos'] as $index => $subgrupo)
            @include('grupos.partials.grupo-item', [
                'grupo' => $subgrupo,
                'nivel' => $nivel + 1,
                'esUltimo' => $index === count($grupo['subgrupos']) - 1,
            ])
        @endforeach
    </div>
@endif

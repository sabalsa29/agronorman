@extends('layouts.web')
@section('title', $section_name)
@section('ruta_home', route('grupos.index'))
@if (Auth::check() && (Auth::user()->canCreate('estaciones.grupos') || Auth::user()->canCreate('usuarios.grupos')))
    @section('ruta_create', route('grupos.create'))
@endif
@section('styles')
    <style>
        .grupos-jerarquia {
            max-height: 700px;
            overflow-y: auto;
            padding: 10px;
        }

        .grupo-item {
            transition: all 0.2s ease;
        }

        .grupo-item:hover {
            transform: translateX(3px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
        }

        .border-left-primary {
            border-left: 4px solid #2196F3 !important;
        }

        .border-left-info {
            border-left: 4px solid #00BCD4 !important;
        }

        .actions-group .btn {
            white-space: nowrap;
        }

        .badge {
            font-size: 11px;
            padding: 4px 8px;
        }

        .subgrupos-container {
            border-left: 2px solid #e0e0e0;
            margin-left: 15px !important;
            padding-left: 10px;
            transition: all 0.3s ease;
        }

        .toggle-subgrupos {
            color: #666;
            text-decoration: none;
        }

        .toggle-subgrupos:hover {
            color: #2196F3;
        }

        .toggle-subgrupos .toggle-icon {
            transition: transform 0.3s ease;
        }

        .toggle-subgrupos.collapsed .toggle-icon {
            transform: rotate(-90deg);
        }

        .subgrupos-container.show {
            display: block !important;
        }

        .grupo-item[data-nivel="0"] {
            margin-bottom: 10px;
        }

        .grupo-item[data-nivel="1"] {
            margin-left: 0;
        }

        .grupo-item[data-nivel="2"] {
            margin-left: 0;
        }

        .grupo-item[data-nivel="3"] {
            margin-left: 0;
        }
    </style>
    <!-- Theme JS files -->
    <script src="{{ url('global_assets/js/plugins/tables/datatables/datatables.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/tables/datatables/extensions/responsive.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/selects/select2.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/buttons/spin.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/buttons/ladda.min.js') }}"></script>

    <script src="{{ url('assets/js/app.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/datatables_responsive.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/components_buttons.js') }}"></script>
    <!-- /theme JS files -->
@endsection
@section('content')
    <!-- Basic responsive configuration -->
    <div class="card">
         <div class="card-header header-elements-inline d-flex justify-content-between align-items-center">
        <div class="header-elements ml-auto">
            <div class="list-icons">
                <a class="list-icons-item" data-action="collapse"></a>
                <a class="list-icons-item" data-action="reload"></a>
                <a class="list-icons-item" data-action="remove"></a>
            </div>
        </div>
    </div>

        <div class="card-body">
            @if (Auth::check() && (Auth::user()->canCreate('estaciones.grupos') || Auth::user()->canCreate('usuarios.grupos')))
                <div class="mb-3">
                    <a href="{{ route('parcelas.assign') }}" class="btn btn-primary">
                        <i class="icon-plus-circle2 mr-2"></i> Crear Asignación de Grupo
                    </a>
                </div>
            @endif
        </div>

        {{-- Vista Jerárquica de Grupos --}}
        <div class="card-body border-top">
            <div class="d-flex justify-content-between align-items-center mb-3"> 
                <h6 class="font-weight-semibold mb-0">
                    <i class="icon-list mr-2"></i> Listado de Asignaciones de Parcelas por Grupos
                </h6>
                @if ($estructuraJerarquica->count() > 0)
                    <div class="text-muted small">
                        <i class="icon-info22 mr-1"></i>
                        Haz clic en <i class="icon-arrow-down8"></i> para expandir/colapsar subgrupos
                    </div>
                @endif
            </div>

            @if ($asignaciones->count() > 0)
                {{-- Barra de búsqueda --}}
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-prepend">
                            <span class="input-group-text"><i class="icon-search4"></i></span>
                        </span>
                        <input type="text" id="buscar-grupo" class="form-control"
                            placeholder="Buscar grupo por nombre...">
                        <span class="input-group-append">
                            <button class="btn btn-light" type="button" id="limpiar-busqueda" style="display: none;">
                                <i class="icon-cross2"></i>
                            </button>
                        </span>
                    </div>
                    <small class="text-muted">
                        <i class="icon-info22 mr-1"></i>
                        Escribe para filtrar grupos. Los grupos que coincidan se expandirán automáticamente.
                    </small>
                </div>
            @endif

            @if ($asignaciones->count() > 0)
                <div class="grupos-jerarquia">
                    @foreach ($asignaciones as $asignacion)
                        <div class="grupo-item mb-3 p-3 border rounded shadow-sm" data-nivel="0">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">
                                    <a href="#" class="toggle-subgrupos collapsed" data-target="#subgrupos-{{ $asignacion->first()->grupo->id }}">
                                        <i class="toggle-icon icon-arrow-down8 mr-2"></i>
                                        {{ $asignacion->first()->grupo->ruta_completa }}
                                    </a>
                                </h6>
                                <span class="badge badge-primary">
                                    <i class="icon-map5 mr-1"></i> {{ $asignacion->count() }} Parcela(s) Asignada(s)
                                </span>
                            </div>
                            <div id="subgrupos-{{ $asignacion->first()->grupo->id }}" class="subgrupos-container mt-3" style="display: none;">
                                <ul class="list-group">
                                    @foreach ($asignacion as $asig)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>
                                                <i class="icon-map mr-2"></i> {{ $asig->parcela->nombre }}
                                            </span>
                                            <form action="{{ route('parcelas.remove') }}" method="POST" class="mb-0">
                                                @csrf
                                                <input type="hidden" name="grupo_id" value="{{ $asig->grupo_id }}">
                                                <input type="hidden" name="parcela_id" value="{{ $asig->parcela_id }}">
                                                <button type="submit" class="btn btn-sm btn-danger delete-button"
                                                    data-name="{{ $asig->parcela->nombre }}">
                                                    <i class="icon-trash mr-1"></i> Quitar
                                                </button>
                                            </form>
                                        </li>
                                        @endforeach
                                </ul>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-info">
                    <i class="icon-info22 mr-2"></i>
                    No hay asignaciones disponibles.
                    @if (Auth::check() && (Auth::user()->canCreate('estaciones.grupos') || Auth::user()->canCreate('usuarios.grupos')))
                        {{--  <a href="{{ route('grupos.create') }}" class="alert-link">Crear el primer grupo</a>  --}}
                    @endif
                </div>
            @endif
        </div>
    </div>
    <!-- /basic responsive configuration -->
@endsection
@section('scripts')
    <script>
        $(document).on('click', '.delete-button', function(e) {
            e.preventDefault();
            const form = $(this).closest('form');
            const name = $(this).data('name') ?? '¿Estás seguro?';

            Swal.fire({
                title: '¿Estás seguro?',
                text: `Vas a eliminar: ${name}`,
                icon: 'warning',
                customClass: {
                    icon: 'swal2-icon-sm'
                },
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        // Manejar expandir/colapsar subgrupos
        $(document).on('click', '.toggle-subgrupos', function(e) {
            e.preventDefault();
            const $button = $(this);
            const $container = $($button.data('target'));
            const $icon = $button.find('.toggle-icon');

            if ($container.is(':visible')) {
                // Colapsar
                $container.slideUp(300);
                $button.addClass('collapsed');
            } else {
                // Expandir
                $container.slideDown(300);
                $button.removeClass('collapsed');
            }
        });

        // Opcional: Expandir todos / Colapsar todos
        $(document).ready(function() {
            // Agregar botones de control global si hay grupos
            @if ($estructuraJerarquica->count() > 0)
                const $header = $('.card-header .header-elements');
                if ($header.length) {
                    const $controls = $('<div class="btn-group mr-2"></div>');
                    $controls.append(
                        '<button type="button" class="btn btn-sm btn-outline-primary" id="expand-all">' +
                        '<i class="icon-arrow-down8 mr-1"></i> Expandir Todo</button>'
                    );
                    $controls.append(
                        '<button type="button" class="btn btn-sm btn-outline-secondary" id="collapse-all">' +
                        '<i class="icon-arrow-up8 mr-1"></i> Colapsar Todo</button>'
                    );
                    $header.prepend($controls);
                }

                $('#expand-all').on('click', function() {
                    $('.subgrupos-container').slideDown(300);
                    $('.toggle-subgrupos').removeClass('collapsed');
                });

                $('#collapse-all').on('click', function() {
                    $('.subgrupos-container').slideUp(300);
                    $('.toggle-subgrupos').addClass('collapsed');
                });

                // Funcionalidad de búsqueda
                let searchTimeout;
                $('#buscar-grupo').on('input', function() {
                    const termino = $(this).val().toLowerCase().trim();
                    const $limpiarBtn = $('#limpiar-busqueda');

                    if (termino.length > 0) {
                        $limpiarBtn.show();

                        clearTimeout(searchTimeout);
                        searchTimeout = setTimeout(function() {
                            buscarGrupos(termino);
                        }, 300); // Debounce de 300ms
                    } else {
                        $limpiarBtn.hide();
                        // Mostrar todos los grupos
                        $('.grupo-item').show();
                        $('.subgrupos-container').hide();
                        $('.toggle-subgrupos').addClass('collapsed');
                    }
                });

                $('#limpiar-busqueda').on('click', function() {
                    $('#buscar-grupo').val('');
                    $(this).hide();
                    $('.grupo-item').show();
                    $('.subgrupos-container').hide();
                    $('.toggle-subgrupos').addClass('collapsed');
                });

                function buscarGrupos(termino) {
                    let encontrados = 0;

                    $('.grupo-item').each(function() {
                        const $item = $(this);
                        const nombreGrupo = $item.find('h6').text().toLowerCase();
                        const $subgruposContainer = $item.next('.subgrupos-container');

                        if (nombreGrupo.includes(termino)) {
                            // Mostrar este grupo
                            $item.show();
                            encontrados++;

                            // Expandir y mostrar subgrupos si existen
                            if ($subgruposContainer.length) {
                                $subgruposContainer.slideDown(200);
                                $item.find('.toggle-subgrupos').removeClass('collapsed');

                                // Buscar en subgrupos también
                                buscarEnSubgrupos($subgruposContainer, termino);
                            }
                        } else {
                            // Verificar si algún subgrupo coincide
                            if ($subgruposContainer.length) {
                                const tieneCoincidencia = buscarEnSubgrupos($subgruposContainer, termino);
                                if (tieneCoincidencia) {
                                    $item.show();
                                    $subgruposContainer.slideDown(200);
                                    $item.find('.toggle-subgrupos').removeClass('collapsed');
                                    encontrados++;
                                } else {
                                    $item.hide();
                                }
                            } else {
                                $item.hide();
                            }
                        }
                    });

                    // Mostrar mensaje si no se encontró nada
                    if (encontrados === 0) {
                        if ($('.no-results-message').length === 0) {
                            $('.grupos-jerarquia').append(
                                '<div class="alert alert-warning no-results-message">' +
                                '<i class="icon-warning mr-2"></i>No se encontraron grupos que coincidan con "' +
                                termino + '"' +
                                '</div>'
                            );
                        }
                    } else {
                        $('.no-results-message').remove();
                    }
                }

                function buscarEnSubgrupos($container, termino) {
                    let tieneCoincidencia = false;

                    $container.find('.grupo-item').each(function() {
                        const $subItem = $(this);
                        const nombreSubGrupo = $subItem.find('h6').text().toLowerCase();
                        const $subSubgrupos = $subItem.next('.subgrupos-container');

                        if (nombreSubGrupo.includes(termino)) {
                            $subItem.show();
                            tieneCoincidencia = true;

                            // Expandir sub-subgrupos si existen
                            if ($subSubgrupos.length) {
                                $subSubgrupos.slideDown(200);
                                $subItem.find('.toggle-subgrupos').removeClass('collapsed');
                                buscarEnSubgrupos($subSubgrupos, termino);
                            }
                        } else {
                            // Buscar recursivamente
                            if ($subSubgrupos.length) {
                                const tieneEnSub = buscarEnSubgrupos($subSubgrupos, termino);
                                if (tieneEnSub) {
                                    $subItem.show();
                                    $subSubgrupos.slideDown(200);
                                    $subItem.find('.toggle-subgrupos').removeClass('collapsed');
                                    tieneCoincidencia = true;
                                } else {
                                    $subItem.hide();
                                }
                            } else {
                                $subItem.hide();
                            }
                        }
                    });

                    return tieneCoincidencia;
                }
            @endif
        });
    </script>
@endsection

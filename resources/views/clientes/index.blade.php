@extends('layouts.web')
@section('title', $section_name)
@section('ruta_home', route('clientes.index'))
@if (Auth::check() && Auth::user()->isSuperAdmin())
    @section('ruta_create', route('clientes.create'))
@endif
@section('styles')
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
    <!-- Mensajes de éxito y error -->
    @if (session('success'))
        <div class="alert alert-success alert-styled-left alert-arrow-left alert-dismissible">
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            <span class="font-weight-semibold">{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-styled-left alert-arrow-left alert-dismissible">
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            <span class="font-weight-semibold">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Basic responsive configuration -->
    <div class="card">
        <div class="card-header header-elements-inline">
            <h5 class="card-title">{{ $section_name }}</h5>
            <div class="header-elements">
                <div class="list-icons">
                    <a class="list-icons-item" data-action="collapse"></a>
                    <a class="list-icons-item" data-action="reload"></a>
                    <a class="list-icons-item" data-action="remove"></a>
                </div>
            </div>
        </div>

        <div class="card-body">
            {{ $section_description }}
            <div class="mb-3">
                <a href="" class="btn btn-danger">Descargar PDF</a>
                <a href="" class="btn btn-success">Descargar Excel</a>
            </div>
        </div>

        <table class="table datatable-responsive">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Empresa</th>
                    <th>Teléfono</th>
                    <th>Ubicación</th>
                    <th>Estatus</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $row)
                    <tr>
                        <td>{{ $row->nombre ?? 'Sin nombre' }}</td>
                        <td>{{ $row->empresa ?? 'Sin empresa' }}</td>
                        <td>{{ $row->telefono ?? 'Sin teléfono' }}</td>
                        <td>{{ $row->ubicacion ?? 'Sin ubicación' }}</td>
                        <td>
                            @if ($row->status == 1)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-danger">Inactivo</span>
                            @endif
                        <td class="text-center">
                            <div class="list-icons">
                                <a href="{{ route('clientes.show', [$row]) }}" class="list-icons-item text-info-600 pr-3"
                                    title="Ver detalles">
                                    <i class="icon-eye"></i>
                                </a>
                                @if (Auth::check() && Auth::user()->isSuperAdmin())
                                    <a href="{{ route('clientes.edit', [$row]) }}" class="list-icons-item text-primary-600"
                                        title="Editar">
                                        <i class="icon-pencil7"></i>
                                    </a>
                                    <form action="{{ route('clientes.destroy', $row) }}" method="POST"
                                        class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn p-0 pl-3 border-0 bg-transparent delete-button"
                                            data-name="{{ $row->nombre }}" title="Eliminar">
                                            <i class="icon-trash text-danger-600"></i>
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('parcelas.index', [$row]) }}" class="list-icons-item text-success"
                                    title="Ver parcelas">
                                    <i class="icon-menu6 ml-3 mr-3"></i>
                                </a>
                                <a href="{{ route('usuarios.index', [$row]) }}" class="list-icons-item text-warning"
                                    title="Ver usuarios">
                                    <i class="icon-users"></i>
                                </a>
                                @if (Auth::check() && Auth::user()->isSuperAdmin())
                                    <a href="{{ route('clientes.grupos', [$row]) }}" class="list-icons-item text-info"
                                        title="Gestionar grupos">
                                        <i class="icon-collaboration"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center">No hay registros disponibles.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
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
    </script>
@endsection

@extends('layouts.web')
@section('title', $section_name)
@section('ruta_home', route('estaciones.index'))
@if (Auth::check() && Auth::user()->canCreate('estaciones.alta'))
    @section('ruta_create', route('estaciones.create'))
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
        </div>

        <table class="table datatable-responsive">
            <thead>
                <tr>
                    <th>Fabricante</th>
                    <th>Tipo de estación</th>
                    <th>Usuario</th>
                    <th>Zona de Manejo</th>
                    <th>UUID</th>
                    <th>Celular</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $row)
                    <tr>
                        <td>{{ $row->fabricante->nombre ?? 'S/F' }}</td>
                        <td>{{ $row->tipoEstacion->nombre ?? 'S/T' }}</td>
                        <td>{{ $row->donde ?? 'S/C' }}</td>
                        <td>{{ $row->zona ?? 'S/Z' }}</td>
                        <td>{{ $row->uuid }}</td>
                        <td>{{ $row->celular }}</td>
                        <td class="text-center">
                            <div class="list-icons">
                                @if (Auth::check() && Auth::user()->canEdit('estaciones.alta'))
                                    <a href="{{ route('estaciones.edit', [$row]) }}"
                                        class="list-icons-item text-primary-600"><i class="icon-pencil7"></i></a>
                                @endif
                                @if (Auth::check() && Auth::user()->canDelete('estaciones.alta'))
                                    <form action="{{ route('estaciones.destroy', $row) }}" method="POST"
                                        class="d-inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn p-0 pl-3 border-0 bg-transparent delete-button"
                                            data-name="{{ $row->uuid }}" title="Eliminar">
                                            <i class="icon-trash text-danger-600"></i>
                                        </button>
                                    </form>
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

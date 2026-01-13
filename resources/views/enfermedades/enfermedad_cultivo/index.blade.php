@extends('layouts.web')
@section('title', 'Enfermedades')
@section('ruta_create', route('enfermedades.cultivos.create', $enfermedad))
@section('ruta_home', route('enfermedades.index'))
@section('ruta_alternativa', route('enfermedades.cultivos.index', $enfermedad))
@section('title_ruta_interna', $section_name)
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
                    <th>Cultivo</th>
                    <th>Temperatura min</th>
                    <th>Temperatura max</th>
                    <th>Humedad min</th>
                    <th>Humedad max</th>
                    <th>Alerta preventiva</th>
                    <th>Alerta de riesgo</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $row)
                    <tr>
                        <td>{{ $row->tipoCultivo->nombre }}</td>
                        <td>{{ $row->riesgo_temperatura }}</td>
                        <td>{{ $row->riesgo_temperatura_max }}</td>
                        <td>{{ $row->riesgo_humedad }}</td>
                        <td>{{ $row->riesgo_humedad_max }}</td>
                        <td>{{ $row->riesgo_medio }}</td>
                        <td>{{ $row->riesgo_mediciones }}</td>
                        <td class="text-center">
                            <div class="list-icons">
                                <a href="{{ route('enfermedades.cultivos.edit', ['enfermedad' => $row->enfermedad_id, 'tipoCultivo' => $row->tipo_cultivo_id]) }}"
                                    class="list-icons-item text-primary-600"><i class="icon-pencil7"></i></a>
                                <form
                                    action="{{ route('enfermedades.cultivos.destroy', ['enfermedad' => $row->enfermedad_id, 'tipoCultivo' => $row->tipo_cultivo_id]) }}"
                                    method="POST" class="d-inline delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn p-0 pl-3 border-0 bg-transparent delete-button"
                                        data-name="{{ $row->tipoCultivo->nombre }}" title="Eliminar">
                                        <i class="icon-trash text-danger-600"></i>
                                    </button>
                                </form>
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

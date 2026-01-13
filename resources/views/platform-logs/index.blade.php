@extends('layouts.web')
@section('title', $section_name)
@section('ruta_home', route('platform-logs.index'))
@section('styles')
    <!-- Theme JS files -->
    <script src="{{ url('global_assets/js/plugins/tables/datatables/datatables.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/tables/datatables/extensions/responsive.min.js') }}"></script>
    <script src="{{ url('assets/js/app.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/datatables_responsive.js') }}"></script>
    <!-- /theme JS files -->
@endsection
@section('content')
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
            <p class="mb-4">{{ $section_description }}</p>
            <p class="text-muted">
                <i class="icon-info22"></i> Este log es de solo lectura. Solo el Super Administrador puede acceder a esta
                sección.
            </p>

            {{-- Filtros --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="icon-filter4 mr-2"></i> Filtros
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('platform-logs.index') }}" id="filtrosForm">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="seccion" class="font-weight-semibold">Sección:</label>
                                    <select name="seccion" id="seccion" class="form-control">
                                        <option value="">Todas las secciones</option>
                                        @foreach ($secciones as $sec)
                                            <option value="{{ $sec }}"
                                                {{ $filtros['seccion'] == $sec ? 'selected' : '' }}>
                                                {{ ucfirst($sec) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="accion" class="font-weight-semibold">Acción:</label>
                                    <select name="accion" id="accion" class="form-control">
                                        <option value="">Todas las acciones</option>
                                        @foreach ($acciones as $acc)
                                            <option value="{{ $acc }}"
                                                {{ $filtros['accion'] == $acc ? 'selected' : '' }}>
                                                {{ ucfirst(str_replace('_', ' ', $acc)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="entidad_tipo" class="font-weight-semibold">Entidad:</label>
                                    <select name="entidad_tipo" id="entidad_tipo" class="form-control">
                                        <option value="">Todas las entidades</option>
                                        @foreach ($entidades as $ent)
                                            <option value="{{ $ent }}"
                                                {{ $filtros['entidad_tipo'] == $ent ? 'selected' : '' }}>
                                                {{ $ent }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="usuario_id" class="font-weight-semibold">Usuario:</label>
                                    <select name="usuario_id" id="usuario_id" class="form-control">
                                        <option value="">Todos los usuarios</option>
                                        @foreach ($usuarios as $usr)
                                            <option value="{{ $usr->id }}"
                                                {{ $filtros['usuario_id'] == $usr->id ? 'selected' : '' }}>
                                                {{ $usr->nombre }} ({{ $usr->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha_desde" class="font-weight-semibold">Fecha Desde:</label>
                                    <input type="date" name="fecha_desde" id="fecha_desde" class="form-control"
                                        value="{{ $filtros['fecha_desde'] }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha_hasta" class="font-weight-semibold">Fecha Hasta:</label>
                                    <input type="date" name="fecha_hasta" id="fecha_hasta" class="form-control"
                                        value="{{ $filtros['fecha_hasta'] }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-semibold">&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="icon-filter4 mr-1"></i> Filtrar
                                        </button>
                                        <a href="{{ route('platform-logs.index') }}" class="btn btn-light">
                                            <i class="icon-cross2 mr-1"></i> Limpiar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <table class="table datatable-responsive" id="logsTable">
                <thead>
                    <tr>
                        <th>Fecha/Hora</th>
                        <th>Usuario</th>
                        <th>Sección</th>
                        <th>Acción</th>
                        <th>Entidad</th>
                        <th>Descripción</th>
                        <th>IP</th>
                        <th>Detalles</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>
                                <span class="badge badge-info">{{ $log->username }}</span>
                                @if ($log->usuario)
                                    <br><small class="text-muted">{{ $log->usuario->email }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-secondary">{{ ucfirst($log->seccion) }}</span>
                            </td>
                            <td>
                                @php
                                    $badgeClass = match ($log->accion) {
                                        'crear' => 'badge-success',
                                        'editar', 'actualizar' => 'badge-warning',
                                        'eliminar', 'borrar' => 'badge-danger',
                                        'ver', 'ver_lista' => 'badge-info',
                                        default => 'badge-default',
                                    };
                                @endphp
                                <span
                                    class="badge {{ $badgeClass }}">{{ ucfirst(str_replace('_', ' ', $log->accion)) }}</span>
                            </td>
                            <td>
                                @if ($log->entidad_tipo)
                                    <span class="badge badge-primary">{{ $log->entidad_tipo }}</span>
                                    @if ($log->entidad_id)
                                        <br><small class="text-muted">ID: {{ $log->entidad_id }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ Str::limit($log->descripcion, 80) }}</td>
                            <td>{{ $log->ip_address ?? 'N/A' }}</td>
                            <td>
                                <a href="{{ route('platform-logs.show', $log->id) }}" class="btn btn-sm btn-info">
                                    <i class="icon-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No hay registros disponibles.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Paginación --}}
            <div class="mt-3">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
            // Verificar si la tabla ya está inicializada y destruirla si es necesario
            if ($.fn.DataTable.isDataTable('#logsTable')) {
                $('#logsTable').DataTable().destroy();
            }

            // Inicializar DataTable
            $('#logsTable').DataTable({
                responsive: true,
                order: [
                    [0, 'desc']
                ], // Ordenar por fecha descendente
                pageLength: 25,
                language: {
                    url: "{{ url('global_assets/js/plugins/tables/datatables/Spanish.json') }}"
                },
                destroy: true, // Permite reinicializar la tabla
                paging: false, // Desactivar paginación de DataTables (usamos paginación de Laravel)
                info: false,
            });
        });
    </script>
@endsection

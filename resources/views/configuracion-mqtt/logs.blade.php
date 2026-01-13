@extends('layouts.web')
@section('title', $section_name)
@section('ruta_home', route('configuracion-mqtt.logs'))
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
                <i class="icon-info22"></i> Este log es de solo lectura. No se pueden editar ni eliminar registros.
            </p>

            <div class="mb-3">
                <a href="{{ route('configuracion-mqtt.index') }}" class="btn btn-primary">
                    <i class="icon-arrow-left8 mr-2"></i> Volver a Configuración
                </a>
            </div>

            <table class="table datatable-responsive" id="logsTable">
                <thead>
                    <tr>
                        <th>Fecha/Hora</th>
                        <th>Usuario</th>
                        <th>Acción</th>
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
                            </td>
                            <td>
                                @php
                                    $badgeClass = match($log->accion) {
                                        'login' => 'badge-success',
                                        'logout' => 'badge-warning',
                                        'enviar_configuracion' => 'badge-primary',
                                        'login_fallido' => 'badge-danger',
                                        'acceso_pantalla' => 'badge-secondary',
                                        default => 'badge-default',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ ucfirst(str_replace('_', ' ', $log->accion)) }}</span>
                            </td>
                            <td>{{ $log->descripcion }}</td>
                            <td>{{ $log->ip_address ?? 'N/A' }}</td>
                            <td>
                                @if($log->datos_adicionales)
                                    <button type="button" class="btn btn-sm btn-info" data-toggle="modal" data-target="#modalDetalles{{ $log->id }}">
                                        <i class="icon-eye"></i> Ver
                                    </button>
                                    
                                    <!-- Modal para detalles -->
                                    <div class="modal fade" id="modalDetalles{{ $log->id }}" tabindex="-1" role="dialog">
                                        <div class="modal-dialog modal-lg" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Detalles de la Acción</h5>
                                                    <button type="button" class="close" data-dismiss="modal">
                                                        <span>&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <pre class="bg-light p-3 rounded">{{ json_encode($log->datos_adicionales, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                                    @if($log->user_agent)
                                                        <p class="mt-3"><strong>User Agent:</strong> {{ $log->user_agent }}</p>
                                                    @endif
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No hay registros disponibles.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
                order: [[0, 'desc']], // Ordenar por fecha descendente
                pageLength: 50,
                language: {
                    url: "{{ url('global_assets/js/plugins/tables/datatables/Spanish.json') }}"
                },
                destroy: true // Permite reinicializar la tabla
            });
        });
    </script>
@endsection


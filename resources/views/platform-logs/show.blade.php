@extends('layouts.web')
@section('title', $section_name)
@section('ruta_home', route('platform-logs.index'))
@section('content')
    <div class="card">
        <div class="card-header header-elements-inline">
            <h5 class="card-title">{{ $section_name }}</h5>
            <div class="header-elements">
                <a href="{{ route('platform-logs.index') }}" class="btn btn-light btn-sm">
                    <i class="icon-arrow-left7 mr-2"></i> Volver a Logs
                </a>
                <div class="list-icons">
                    <a class="list-icons-item" data-action="collapse"></a>
                    <a class="list-icons-item" data-action="reload"></a>
                    <a class="list-icons-item" data-action="remove"></a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="font-weight-semibold mb-3">Información General</h6>
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%">Fecha/Hora:</th>
                            <td>{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                        </tr>
                        <tr>
                            <th>Usuario:</th>
                            <td>
                                <span class="badge badge-info">{{ $log->username }}</span>
                                @if ($log->usuario)
                                    <br><small class="text-muted">{{ $log->usuario->email }}</small>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Sección:</th>
                            <td><span class="badge badge-secondary">{{ ucfirst($log->seccion) }}</span></td>
                        </tr>
                        <tr>
                            <th>Acción:</th>
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
                        </tr>
                        <tr>
                            <th>Entidad:</th>
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
                        </tr>
                        <tr>
                            <th>Descripción:</th>
                            <td>{{ $log->descripcion }}</td>
                        </tr>
                    </table>
                </div>

                <div class="col-md-6">
                    <h6 class="font-weight-semibold mb-3">Información Técnica</h6>
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%">IP Address:</th>
                            <td>{{ $log->ip_address ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>User Agent:</th>
                            <td>
                                <small>{{ $log->user_agent ?? 'N/A' }}</small>
                            </td>
                        </tr>
                        <tr>
                            <th>ID del Log:</th>
                            <td>#{{ $log->id }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if ($log->datos_anteriores || $log->datos_nuevos || $log->datos_adicionales)
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h6 class="font-weight-semibold mb-3">Datos de la Acción</h6>
                    </div>

                    @if ($log->datos_anteriores)
                        <div class="col-md-6">
                            <div class="card border-warning">
                                <div class="card-header bg-warning text-white">
                                    <h6 class="card-title mb-0">
                                        <i class="icon-arrow-left8 mr-2"></i> Datos Anteriores
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <pre class="mb-0" style="max-height: 300px; overflow-y: auto;">{{ json_encode($log->datos_anteriores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($log->datos_nuevos)
                        <div class="col-md-6">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h6 class="card-title mb-0">
                                        <i class="icon-arrow-right8 mr-2"></i> Datos Nuevos
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <pre class="mb-0" style="max-height: 300px; overflow-y: auto;">{{ json_encode($log->datos_nuevos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($log->datos_adicionales)
                        <div class="col-md-12 mt-3">
                            <div class="card border-info">
                                <div class="card-header bg-info text-white">
                                    <h6 class="card-title mb-0">
                                        <i class="icon-info22 mr-2"></i> Datos Adicionales
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <pre class="mb-0" style="max-height: 300px; overflow-y: auto;">{{ json_encode($log->datos_adicionales, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection

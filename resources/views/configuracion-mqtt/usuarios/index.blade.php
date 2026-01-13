@extends('layouts.web')
@section('title', $section_name)
@section('ruta_home', route('configuracion-mqtt.usuarios.index'))
@section('styles')
    <!-- Theme JS files -->
    <script src="{{ url('global_assets/js/plugins/tables/datatables/datatables.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/selects/select2.min.js') }}"></script>
    <script src="{{ url('assets/js/app.js') }}"></script>
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

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    {{ session('success') }}
                </div>
            @endif

            <div class="mb-3">
                <a href="{{ route('configuracion-mqtt.usuarios.create') }}" class="btn btn-primary">
                    <i class="icon-plus-circle2 mr-2"></i> Crear Usuario
                </a>
            </div>

            <table class="table table-striped table-hover" id="usuariosTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Estado</th>
                        <th>Estaciones Permitidas</th>
                        <th>Parámetros Permitidos</th>
                        <th>Creado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($usuarios as $usuario)
                        <tr>
                            <td>{{ $usuario->id }}</td>
                            <td>{{ $usuario->username }}</td>
                            <td>
                                @if ($usuario->activo)
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-danger">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                @if ($usuario->estaciones_permitidas === null)
                                    <span class="text-muted">Todas las estaciones</span>
                                @else
                                    <span class="badge badge-info">{{ count($usuario->estaciones_permitidas) }} estación(es)</span>
                                @endif
                            </td>
                            <td>
                                @if ($usuario->parametros_permitidos === null)
                                    <span class="text-muted">Todos los parámetros</span>
                                @else
                                    @php
                                        $parametros = array_filter($usuario->parametros_permitidos ?? []);
                                        $parametrosNombres = array_keys($parametros);
                                    @endphp
                                    <span class="badge badge-info">{{ implode(', ', $parametrosNombres) }}</span>
                                @endif
                            </td>
                            <td>{{ $usuario->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('configuracion-mqtt.usuarios.edit', $usuario->id) }}" class="btn btn-sm btn-primary">
                                    <i class="icon-pencil7"></i> Editar
                                </a>
                                <form action="{{ route('configuracion-mqtt.usuarios.destroy', $usuario->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar este usuario?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="icon-trash"></i> Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#usuariosTable')) {
                $('#usuariosTable').DataTable().destroy();
            }
            
            $('#usuariosTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 25,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json'
                }
            });
        });
    </script>
@endsection


@extends('layouts.web')
@section('title', $section_name)
@section('ruta_create', route('enfermedades.create'))
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

        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th>ID</th>
                            <td>{{ $enfermedad->id }}</td>
                        </tr>
                        <tr>
                            <th>Nombre</th>
                            <td>{{ $enfermedad->nombre }}</td>
                        </tr>
                        <tr>
                            <th>Slug</th>
                            <td>{{ $enfermedad->slug }}</td>
                        </tr>
                        <tr>
                            <th>Estado</th>
                            <td>
                                @if($enfermedad->status)
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-danger">Inactivo</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Creado</th>
                            <td>{{ $enfermedad->created_at }}</td>
                        </tr>
                        <tr>
                            <th>Actualizado</th>
                            <td>{{ $enfermedad->updated_at }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="text-right">
                <a href="{{ route('enfermedades.edit', $enfermedad) }}" class="btn btn-primary">
                    <i class="icon-pencil7 mr-2"></i>Editar
                </a>
                <a href="{{ route('enfermedades.index') }}" class="btn btn-secondary">
                    <i class="icon-arrow-left8 mr-2"></i>Volver
                </a>
            </div>
        </div>
    </div>
    <!-- /basic responsive configuration -->
@endsection 
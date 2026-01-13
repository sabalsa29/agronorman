@extends('layouts.web')
@section('title', $section_name)
@section('modelo', 'Editar')
@section('ruta_home', route('grupos.index'))
@section('styles')
    <!-- Theme JS files -->
    <script src="{{ url('global_assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/styling/switchery.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/selects/select2.min.js') }}"></script>

    <script src="{{ url('assets/js/app.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/form_inputs.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/table_elements.js') }}"></script>
    <!-- /theme JS files -->
@endsection
@section('content')
    <!-- Form inputs -->
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

            <form action="{{ route('grupos.update', $grupo) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <fieldset class="mb-3">
                    <div class="form-group row">
                        <label class="col-form-label col-lg-2">Nombre <span class="text-danger">*</span></label>
                        <div class="col-lg-10">
                            <input type="text" name="nombre" value="{{ old('nombre', $grupo->nombre) }}" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-form-label col-lg-2">Grupo Padre</label>
                        <div class="col-lg-10">
                            <select name="grupo_id" id="grupo-padre" class="form-control select2">
                                <option value="">(Sin grupo padre - Grupo raíz)</option>
                                @foreach($gruposDisponibles as $grupoDisponible)
                                    <option value="{{ $grupoDisponible['id'] }}" 
                                            {{ old('grupo_id', $grupo->grupo_id) == $grupoDisponible['id'] ? 'selected' : '' }}>
                                        {{ $grupoDisponible['nombre'] }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="form-text text-muted">
                                Seleccione un grupo padre para crear un subgrupo, o déjelo vacío para crear un grupo raíz
                            </span>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-form-label col-lg-2">Estatus</label>
                        <div class="col-lg-10">
                            <div class="form-check form-check-inline form-check-switchery">
                                <label class="form-check-label">
                                    <!-- Enviará 0 si no está activado -->
                                    <input type="hidden" name="status" @checked(old('status', $grupo->status) == 0) value="0">
                                    <!-- Enviará 1 si está activado -->
                                    <input type="checkbox" name="status" @checked(old('status', $grupo->status) == 1) value="1"
                                        class="form-input-switchery">
                                </label>
                            </div>
                        </div>
                    </div>

                </fieldset>

                <div class="text-right">
                    <button type="submit" class="btn btn-primary">Actualizar <i class="icon-paperplane ml-2"></i></button>
                </div>
            </form>
        </div>
    </div>
    <!-- /form inputs -->
@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
            // Inicializar Select2
            $('.select2').select2({
                placeholder: 'Seleccione un grupo padre (opcional)',
                allowClear: true
            });
        });
    </script>
@endsection

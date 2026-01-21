@extends('layouts.web')
@section('title', $section_name)
@section('modelo', 'Agregar')
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

            <form action="{{ route('parcelas.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <fieldset class="mb-3">

                     <div class="form-group row">
                        <label class="col-form-label col-lg-2">
                            <i class="icon-collaboration mr-1"></i>
                            Grupo <span class="text-danger">*</span>
                        </label>
                        <div class="col-lg-10">
                            <select name="grupo_id" id="grupo-padre" class="form-control select2">
                                <option value="">(Sin grupo)</option>
                                @foreach ($gruposDisponibles as $grupo)
                                    <option value="{{ $grupo['id'] }}"
                                        {{ old('grupo_id', $grupoPadreId ?? null) == $grupo['id'] ? 'selected' : '' }}>
                                        {{ $grupo['nombre'] }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="form-text text-muted">
                                <i class="icon-info22 mr-1"></i>
                                Seleccione un grupo
                            </span>
                        </div>
                    </div>
                    {{--  lids  --}}
                    <div class="form-group row">
                        <label class="col-form-label col-lg-2"><i class="icon-map5"></i> Parcela <span class="text-danger">*</span></label>
                        <div class="col-lg-10">
                            <select name="parcela_ids[]" id="parcela_id" class="form-control parcela_id select2" multiple="multiple">
                                <option value="">(Sin seleccion)</option>
                                @foreach ($parcelas as $parcela)
                                    <option value="{{ $parcela['id'] }}"
                                        {{ old('parcela_id', $parcelaId ?? null) == $parcela['id'] ? 'selected' : '' }}>
                                        {{ $parcela['nombre'] }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="form-text text-muted">
                                <i class="icon-info22 mr-1"></i>
                                Seleccione al menos una parcela para asignarla a un grupo, es obligatorio
                            </span>
                        </div>
                    </div>
                   

                    <div class="form-group row">
                        <label class="col-form-label col-lg-2">Estatus</label>
                        <div class="col-lg-10">
                            <div class="form-check form-check-inline form-check-switchery">
                                <label class="form-check-label">
                                    <!-- Enviará 0 si no está activado -->
                                    <input type="hidden" name="status" value="0">
                                    <!-- Enviará 1 si está activado -->
                                    <input type="checkbox" name="status" value="1" class="form-input-switchery"
                                        {{ old('status', 1) ? 'checked' : '' }} data-fouc>
                                </label>
                            </div>
                        </div>
                    </div>

                </fieldset>

                <div class="text-right">
                    <button type="submit" class="btn btn-primary">Agregar <i class="icon-paperplane ml-2"></i></button>
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
                placeholder: 'Seleccione un grupo',
                allowClear: true
            });

            $('.parcela_id').select2({
                placeholder: 'Seleccione una parcela',
                allowClear: true
            });
        });
    </script>
@endsection

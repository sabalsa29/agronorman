@extends('layouts.web')
@section('title', $section_name)
@section('modelo', 'Actualizar')
@section('ruta_home', route('clientes.index'))
@section('ruta_alternativa', route('parcelas.index', ['id' => $cliente_id]))
@section('title_ruta_interna', 'Parcelas')
@section('ruta_create', route('zona_manejo.create', ['id' => $cliente_id, 'parcela_id' => $parcela_id]))
@section('styles')
    <!-- Theme JS files -->
    <script src="{{ url('global_assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/selects/bootstrap_multiselect.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/selects/select2.min.js') }}"></script>

    <script src="{{ url('assets/js/app.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/form_inputs.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/form_multiselect.js') }}"></script>
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

            <form
                action="{{ route('zona_manejo.update', ['id' => $cliente_id, 'parcela_id' => $parcela_id, $zona_manejo]) }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <fieldset class="mb-3">

                    <div class="form-group row">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-4">
                                    <label class="col-form-label col-lg-12">Nombre <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="nombre" value="{{ $zona_manejo->nombre }}"
                                        class="form-control" placeholder="Nombre">
                                    <input type="hidden" name="parcela_id" value="{{ $parcela_id }}">
                                    <input type="hidden" name="cliente_id" value="{{ $cliente_id }}">
                                </div>

                                <div class="col-4">
                                    <label class="col-form-label col-lg-12">Tipo de Cultivo <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control multiselect-select-all-filtering" name="tipo_cultivo_id[]"
                                        multiple="multiple" data-fouc>
                                        @foreach ($cultivos as $cultivo)
                                            <optgroup label="{{ $cultivo->nombre }}">
                                                @foreach ($cultivo->tipo_cultivos as $tipo)
                                                    <option value="{{ $tipo->id }}"
                                                        {{ in_array($tipo->id, $zona_manejo->tipoCultivos->pluck('id')->toArray()) ? 'selected' : '' }}>
                                                        {{ $tipo->nombre }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>


                                <div class="col-4">
                                    <label class="col-form-label col-lg-12">Fecha inicial UCA <span
                                            class="text-danger">*</span></label>
                                    <input class="form-control" type="date" name="fecha_inicial_uca"
                                        value="{{ $zona_manejo->fecha_inicial_uca }}">
                                </div>

                                <div class="col-6">
                                    <label class="col-form-label col-lg-12">Textura de Suelo <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control" name="tipo_suelo_id">
                                        <option value="">Elige una opción</option>
                                        @foreach ($tipo_suelo as $item)
                                            <option value="{{ $item->id }}"
                                                {{ $zona_manejo->tipo_suelo_id == $item->id ? 'selected' : '' }}>
                                                {{ $item->tipo_suelo }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-6">
                                    <label class="col-form-label col-lg-12">Fecha de siembra <span
                                            class="text-danger">*</span></label>
                                    <input class="form-control" type="date" name="fecha_siembra"
                                        value="{{ $zona_manejo->fecha_siembra }}">
                                </div>

                                <div class="col-12">
                                    <label class="col-form-label col-lg-12">Grupo</label>
                                    <select name="grupo_id" id="grupo-select" class="form-control select2">
                                        <option value="">(Sin grupo - Opcional)</option>
                                        @foreach($gruposDisponibles as $grupo)
                                            <option value="{{ $grupo['id'] }}" 
                                                    {{ old('grupo_id', $zona_manejo->grupo_id) == $grupo['id'] ? 'selected' : '' }}>
                                                {{ $grupo['nombre'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <span class="form-text text-muted">
                                        Seleccione un grupo para asociar esta zona de manejo a la jerarquía de grupos
                                    </span>
                                </div>

                            </div>
                        </div>
                    </div>

                </fieldset>

                <fieldset class="mb-3">

                    <div class="form-group row">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-12">
                                    <label class="col-form-label col-lg-12">Temperatura Base <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="temp_base_calor" class="form-control"
                                        placeholder="Temperatura Base" value="{{ $zona_manejo->temp_base_calor }}">
                                </div>
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
            // Inicializar Select2 para el select de grupo
            $('#grupo-select').select2({
                placeholder: 'Seleccione un grupo (opcional)',
                allowClear: true
            });
        });
    </script>
@endsection

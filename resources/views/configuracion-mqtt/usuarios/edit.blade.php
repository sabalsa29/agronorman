@extends('layouts.web')
@section('title', $section_name)
@section('ruta_home', route('configuracion-mqtt.usuarios.index'))
@section('styles')
    <!-- Theme JS files -->
    <script src="{{ url('global_assets/js/plugins/forms/selects/select2.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
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

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('configuracion-mqtt.usuarios.update', $usuario->id) }}" method="POST">
                @csrf
                @method('PUT')

                <fieldset class="mb-3">
                    <legend class="text-uppercase font-size-sm font-weight-bold">Información Básica</legend>

                    <div class="form-group row">
                        <label class="col-form-label col-lg-2">Username <span class="text-danger">*</span></label>
                        <div class="col-lg-10">
                            <input type="text" name="username" class="form-control"
                                value="{{ old('username', $usuario->username) }}" required>
                            <span class="form-text text-muted">Nombre de usuario único</span>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-form-label col-lg-2">Password</label>
                        <div class="col-lg-10">
                            <input type="password" name="password" class="form-control" minlength="6">
                            <span class="form-text text-muted">Deje vacío para mantener la contraseña actual. Mínimo 6
                                caracteres si desea cambiarla.</span>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-form-label col-lg-2">Estado</label>
                        <div class="col-lg-10">
                            <input type="hidden" name="activo" value="0">
                            <div class="form-check form-check-switch form-check-switch-left">
                                <label class="form-check-label d-flex align-items-center">
                                    <input type="checkbox" name="activo" class="form-check-input-switch" value="1"
                                        {{ old('activo', $usuario->activo) ? 'checked' : '' }} data-on-text="Activo"
                                        data-off-text="Inactivo">
                                    <span class="ml-4">Usuario activo</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="mb-3">
                    <legend class="text-uppercase font-size-sm font-weight-bold">Privilegios de Estaciones</legend>

                    <div class="form-group row">
                        <label class="col-form-label col-lg-2">Estaciones Permitidas</label>
                        <div class="col-lg-10">
                            <select name="estaciones_permitidas[]" class="form-control select2" multiple>
                                @foreach ($estaciones as $estacion)
                                    <option value="{{ $estacion->id }}"
                                        {{ (old('estaciones_permitidas') !== null && in_array($estacion->id, old('estaciones_permitidas'))) ||
                                        (old('estaciones_permitidas') === null &&
                                            $usuario->estaciones_permitidas !== null &&
                                            in_array($estacion->id, $usuario->estaciones_permitidas))
                                            ? 'selected'
                                            : '' }}>
                                        {{ $estacion->uuid }}
                                        @if ($estacion->celular)
                                            - {{ $estacion->celular }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <span class="form-text text-muted">Seleccione las estaciones que este usuario puede modificar.
                                Deje vacío para permitir todas.</span>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="mb-3">
                    <legend class="text-uppercase font-size-sm font-weight-bold">Privilegios de Parámetros</legend>

                    <div class="form-group row">
                        <label class="col-form-label col-lg-2">Parámetros Permitidos</label>
                        <div class="col-lg-10">
                            <div class="form-check form-check-switch form-check-switch-left mb-2">
                                <label class="form-check-label d-flex align-items-center">
                                    <input type="checkbox" name="todos_parametros" id="todos_parametros"
                                        class="form-check-input-switch"
                                        {{ old('todos_parametros') !== null ? (old('todos_parametros') ? 'checked' : '') : ($usuario->parametros_permitidos === null ? 'checked' : '') }}
                                        data-on-text="Sí" data-off-text="No">
                                    <span class="ml-4">Permitir todos los parámetros</span>
                                </label>
                            </div>
                            <div id="parametros_container">
                                @php
                                    $parametrosPermitidos =
                                        old('parametros_permitidos') !== null
                                            ? old('parametros_permitidos')
                                            : $usuario->parametros_permitidos ?? [];
                                @endphp
                                <div class="form-check mb-2">
                                    <label class="form-check-label">
                                        <input type="checkbox" name="parametros_permitidos[PCF]"
                                            class="form-check-input-styled" value="1"
                                            {{ isset($parametrosPermitidos['PCF']) && $parametrosPermitidos['PCF'] ? 'checked' : '' }}>
                                        PCF (Configuración FOTA)
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <label class="form-check-label">
                                        <input type="checkbox" name="parametros_permitidos[PCR]"
                                            class="form-check-input-styled" value="1"
                                            {{ isset($parametrosPermitidos['PCR']) && $parametrosPermitidos['PCR'] ? 'checked' : '' }}>
                                        PCR (Reset diario)
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <label class="form-check-label">
                                        <input type="checkbox" name="parametros_permitidos[PTP]"
                                            class="form-check-input-styled" value="1"
                                            {{ isset($parametrosPermitidos['PTP']) && $parametrosPermitidos['PTP'] ? 'checked' : '' }}>
                                        PTP (Descarga de parámetros)
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <label class="form-check-label">
                                        <input type="checkbox" name="parametros_permitidos[PTC]"
                                            class="form-check-input-styled" value="1"
                                            {{ isset($parametrosPermitidos['PTC']) && $parametrosPermitidos['PTC'] ? 'checked' : '' }}>
                                        PTC (Tiempo de captura/transmisión)
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <label class="form-check-label">
                                        <input type="checkbox" name="parametros_permitidos[PTR]"
                                            class="form-check-input-styled" value="1"
                                            {{ isset($parametrosPermitidos['PTR']) && $parametrosPermitidos['PTR'] ? 'checked' : '' }}>
                                        PTR (Tiempo de reset)
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <label class="form-check-label">
                                        <input type="checkbox" name="parametros_permitidos[PRS]"
                                            class="form-check-input-styled" value="1"
                                            {{ isset($parametrosPermitidos['PRS']) && $parametrosPermitidos['PRS'] ? 'checked' : '' }}>
                                        PRS (Activación lectura de sensor)
                                    </label>
                                </div>
                            </div>
                            <span class="form-text text-muted">Seleccione los parámetros que este usuario puede modificar.
                                Los parámetros no seleccionados usarán valores por defecto al enviar configuración.</span>
                        </div>
                    </div>
                </fieldset>

                <div class="form-group row">
                    <div class="col-lg-10 offset-lg-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="icon-checkmark2 mr-2"></i> Actualizar Usuario
                        </button>
                        <a href="{{ route('configuracion-mqtt.usuarios.index') }}" class="btn btn-light ml-2">
                            <i class="icon-arrow-left7 mr-2"></i> Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
            // Inicializar Select2
            $('.select2').select2({
                placeholder: 'Seleccione estaciones (opcional)',
                allowClear: true
            });

            // Inicializar switches
            $('.form-check-input-switch').uniform();

            // Inicializar checkboxes estilizados
            $('.form-check-input-styled').uniform();

            // Manejar checkbox "todos los parámetros"
            $('#todos_parametros').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#parametros_container input[type="checkbox"]').prop('disabled', true).uniform(
                        'refresh');
                } else {
                    $('#parametros_container input[type="checkbox"]').prop('disabled', false).uniform(
                        'refresh');
                }
            });

            // Inicializar estado según si "todos los parámetros" está marcado
            if ($('#todos_parametros').is(':checked')) {
                $('#parametros_container input[type="checkbox"]').prop('disabled', true).uniform('refresh');
            }
        });
    </script>
@endsection

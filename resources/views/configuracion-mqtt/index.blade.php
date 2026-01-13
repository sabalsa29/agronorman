@extends('layouts.web')
@section('title', $section_name)
@section('ruta_home', route('configuracion-mqtt.index'))
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

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    {{ session('success') }}
                </div>
            @endif

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

            <form action="{{ route('configuracion-mqtt.enviar') }}" method="POST" id="configForm">
                @csrf

                <div class="form-group row">
                    <label class="col-form-label col-lg-2">Estación <span class="text-danger">*</span></label>
                    <div class="col-lg-10">
                        <select name="estacion_id" class="form-control select2" required>
                            <option value="">Seleccione una estación</option>
                            @foreach ($estaciones as $estacion)
                                <option value="{{ $estacion->id }}">
                                    {{ $estacion->uuid }} 
                                    @if($estacion->celular)
                                        - {{ $estacion->celular }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <fieldset class="mb-3">
                    <legend class="text-uppercase font-size-sm font-weight-bold">Parámetros de Configuración</legend>
                    @php
                        $tieneTodosPermisos = !$usuario || $usuario->parametros_permitidos === null;
                    @endphp

                    @if($tieneTodosPermisos || ($usuario && $usuario->tienePermisoParametro('PCF')))
                        <div class="form-group row">
                            <label class="col-form-label col-lg-2">PCF (Configuración FOTA) <span class="text-danger">*</span></label>
                            <div class="col-lg-10">
                                <select name="PCF" class="form-control" required>
                                    <option value="0">0 - No actualizar Firmware</option>
                                    <option value="1">1 - Actualización de Firmware versión producción</option>
                                    <option value="2">2 - Actualización de Firmware versión TEST</option>
                                </select>
                                <span class="form-text text-muted">Rango: 0-2</span>
                            </div>
                        </div>
                    @endif

                    @if($tieneTodosPermisos || ($usuario && $usuario->tienePermisoParametro('PCR')))
                        <div class="form-group row">
                            <label class="col-form-label col-lg-2">PCR (Reset diario) <span class="text-danger">*</span></label>
                            <div class="col-lg-10">
                                <select name="PCR" class="form-control" required>
                                    <option value="0">0 - No reset</option>
                                    <option value="1" selected>1 - Reset diario</option>
                                </select>
                                <span class="form-text text-muted">Rango: 0-1</span>
                            </div>
                        </div>
                    @endif

                    @if($tieneTodosPermisos || ($usuario && $usuario->tienePermisoParametro('PTP')))
                        <div class="form-group row">
                            <label class="col-form-label col-lg-2">PTP (Descarga de parámetros) <span class="text-danger">*</span></label>
                            <div class="col-lg-10">
                                <select name="PTP" class="form-control" required>
                                    <option value="0" selected>0 - Descargar parámetros en cada transmisión</option>
                                    <option value="1">1 - Descargar parámetros una vez cada 1 día</option>
                                    <option value="2">2 - Descargar parámetros una vez cada 2 días</option>
                                    <option value="3">3 - Descargar parámetros una vez cada 3 días</option>
                                    <option value="4">4 - Descargar parámetros una vez cada 4 días</option>
                                    <option value="5">5 - Descargar parámetros una vez cada 5 días</option>
                                    <option value="6">6 - Descargar parámetros una vez cada 6 días</option>
                                    <option value="7">7 - Descargar parámetros una vez cada 7 días</option>
                                </select>
                                <span class="form-text text-muted">Rango: 0-7</span>
                            </div>
                        </div>
                    @endif

                    @if($tieneTodosPermisos || ($usuario && $usuario->tienePermisoParametro('PTC')))
                        <div class="form-group row">
                            <label class="col-form-label col-lg-2">PTC (Tiempo de captura/transmisión) <span class="text-danger">*</span></label>
                            <div class="col-lg-10">
                                <input type="number" name="PTC" class="form-control" value="60" min="15" max="60" required>
                                <span class="form-text text-muted">Tiempo en minutos entre cada transmisión. Rango: 15-60</span>
                            </div>
                        </div>
                    @endif

                    @if($tieneTodosPermisos || ($usuario && $usuario->tienePermisoParametro('PTR')))
                        <div class="form-group row">
                            <label class="col-form-label col-lg-2">PTR (Tiempo de reset) <span class="text-danger">*</span></label>
                            <div class="col-lg-10">
                                <input type="number" name="PTR" class="form-control" value="0" min="0" max="23" required>
                                <span class="form-text text-muted">Hora a la que se realizará el reset (0 = 12 am). Rango: 0-23</span>
                            </div>
                        </div>
                    @endif

                    @if($tieneTodosPermisos || ($usuario && $usuario->tienePermisoParametro('PRS')))
                        <div class="form-group row">
                            <label class="col-form-label col-lg-2">PRS (Activación lectura de sensor) <span class="text-danger">*</span></label>
                            <div class="col-lg-10">
                                <select name="PRS" class="form-control" required>
                                    <option value="0">0 - Desactivado</option>
                                    <option value="1" selected>1 - Activado</option>
                                </select>
                                <span class="form-text text-muted">Rango: 0-1</span>
                            </div>
                        </div>
                    @endif

                    @if(!$tieneTodosPermisos && $usuario && $usuario->parametros_permitidos !== null)
                        <div class="alert alert-info">
                            <strong>Nota:</strong> Solo puedes modificar los parámetros mostrados arriba. Los parámetros no mostrados se enviarán con sus valores por defecto.
                        </div>
                    @endif
                </fieldset>

                <div class="form-group row">
                    <div class="col-lg-10 offset-lg-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="icon-paperplane mr-2"></i> Enviar Configuración
                        </button>
                        <a href="{{ route('configuracion-mqtt.logs') }}" class="btn btn-info ml-2">
                            <i class="icon-list mr-2"></i> Ver Logs
                        </a>
                        <a href="{{ route('configuracion-mqtt.logout') }}" class="btn btn-light ml-2" onclick="return confirm('¿Está seguro de cerrar sesión?');">
                            <i class="icon-exit mr-2"></i> Cerrar Sesión
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
            $('.select2').select2();

            // Prevenir envío múltiple
            $('#configForm').on('submit', function() {
                $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="icon-spinner2 spinner mr-2"></i> Enviando...');
            });

            // Remover atributo required de campos ocultos (por si acaso)
            $('#configForm').on('submit', function(e) {
                $(this).find('input[type="number"], select').each(function() {
                    if (!$(this).is(':visible')) {
                        $(this).removeAttr('required');
                    }
                });
            });
        });
    </script>
@endsection


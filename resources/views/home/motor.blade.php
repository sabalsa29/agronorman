@extends('layouts.web')
@section('title', $section_name)
@section('meta')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection
@section('styles')
    <!-- Custom CSS to hide blocks initially -->
    <!-- Theme JS files -->
    <script src="{{ url('global_assets/js/plugins/extensions/jquery_ui/interactions.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/selects/select2.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/styling/uniform.min.js') }}"></script>

    <script src="{{ url('global_assets/js/plugins/ui/moment/moment.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/pickers/daterangepicker.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/pickers/anytime.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/pickers/pickadate/picker.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/pickers/pickadate/picker.date.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/pickers/pickadate/picker.time.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/pickers/pickadate/legacy.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/notifications/jgrowl.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/visualization/echarts/echarts.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="{{ url('assets/js/app.js') }}"></script>
    <script src="{{ url('js/charts/areas.js') }}"></script>
    <script src="{{ url('js/charts/estres_temperatura.js') }}"></script>
    <script src="{{ url('js/charts/estres_co2.js') }}"></script> {{-- Listo --}}
    <script src="{{ url('js/charts/estres_temperatura_suelo.js') }}"></script> {{-- Listo --}}
    <script src="{{ url('js/charts/estres_ph.js') }}"></script>
    <script src="{{ url('js/charts/estres_phos.js') }}"></script>
    <script src="{{ url('js/charts/estres_nitrogeno.js') }}"></script>
    <script src="{{ url('js/charts/estres_potasio.js') }}"></script>
    <script src="{{ url('js/charts/estres_conductividad_electrica.js') }}"></script>
    <script src="{{ url('js/charts/estres_precipitacion_pluvial.js') }}"></script>
    <script src="{{ url('js/charts/estres_humedad_suelo.js') }}"></script>
    <script src="{{ url('js/charts/estres_humedad_relativa.js') }}"></script> {{-- Listo --}}
    <script src="{{ url('js/charts/estres_pronostico_temperatura.js') }}"></script>
    <script src="{{ url('js/charts/estres_pronostico_humedad_relativa.js') }}"></script>
    <script src="{{ url('js/charts/estres_pronostico_velocidad_viento.js') }}"></script>
    <script src="{{ url('js/charts/estres_pronostico_precipitacion_pluvial.js') }}"></script>
    <script src="{{ url('js/charts/estres_pronostico_presion_atmosferica.js') }}"></script>
    <script src="{{ url('js/charts/variables_multiples.js') }}"></script>
    <script src="{{ url('js/charts/estres_enfermedades.js') }}"></script>
    <script src="{{ url('js/charts/estres_velocidad_viento.js') }}"></script>
    <script src="{{ url('js/charts/estres_presion_atmosferica.js') }}"></script>
    @vite(['resources/js/charts/plagas.js'])
    <script src="{{ url('global_assets/js/demo_pages/form_select2.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/form_inputs.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/picker_date.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/selects/bootstrap_multiselect.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/form_multiselect.js') }}"></script>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script>
        // Cargar la librería de gráficos
        google.charts.load('current', {
            packages: ['corechart', 'bar']
        });
        // google.charts.setOnLoadCallback(function() {
        //     preloadChart('barchart_material_frio');
        // });
    </script>
    <style>
        .loader-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(30, 30, 30, 0.4);
            backdrop-filter: blur(2px);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        /* Estilos para el modal de progreso */
        #modal-progreso .modal-content {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        #modal-progreso .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            border-bottom: none;
        }

        #modal-progreso .modal-title {
            font-weight: 600;
            font-size: 1.1rem;
        }

        #modal-progreso .modal-body {
            padding: 2rem;
            background: #f8f9fa;
        }

        #modal-progreso .progress {
            height: 25px;
            border-radius: 12px;
            background-color: #e9ecef;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        #modal-progreso .progress-bar {
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            line-height: 25px;
            transition: width 0.3s ease;
        }

        #modal-progreso .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: 0.25em;
        }

        #texto-progreso {
            font-size: 0.95rem;
            font-weight: 500;
            color: #6c757d;
            margin-top: 1rem;
        }

        /* Animación para el spinner */
        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        #modal-progreso .spinner-border {
            animation: pulse 2s infinite;
        }

        /* Estados de la barra de progreso */
        #modal-progreso .progress-bar.bg-success {
            background: linear-gradient(90deg, #28a745, #20c997) !important;
        }

        #modal-progreso .progress-bar.bg-danger {
            background: linear-gradient(90deg, #dc3545, #fd7e14) !important;
        }
    </style>
@endsection
@section('content')
    <script>
        // Mostrar el loader lo antes posible al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            var loader = document.getElementById('loader');
            if (loader) loader.style.display = 'flex';
        });
    </script>
    <!-- Content area -->
    <div class="content">
        <!-- Loader -->
        <div id="loader" class="loader-wrapper" style="display:none; z-index: 9999;">
            <div class="loader-spinner" role="status" aria-live="polite">
                <!-- SVG Spinner -->
                <svg width="60" height="60" viewBox="0 0 50 50">
                    <circle cx="25" cy="25" r="20" fill="none" stroke="#e0e0e0" stroke-width="6" />
                    <circle cx="25" cy="25" r="20" fill="none" stroke="#3498db" stroke-width="6"
                        stroke-linecap="round" stroke-dasharray="100, 200" stroke-dashoffset="0">
                        <animateTransform attributeName="transform" type="rotate" from="0 25 25" to="360 25 25"
                            dur="1s" repeatCount="indefinite" />
                    </circle>
                </svg>
            </div>
            <div id="loader-text" class="loader-text">Cargando...</div>
            <button id="close-loader-btn"
                style="display:none; margin-top:20px; padding:8px 20px; border:none; border-radius:4px; background:#e74c3c; color:#fff; font-weight:bold; cursor:pointer;">
                Cerrar loader
            </button>
        </div>

        <style>
            .loader-wrapper {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(30, 30, 30, 0.4);
                backdrop-filter: blur(2px);
                z-index: 9999;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
            }

            .loader-spinner {
                margin-bottom: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .loader-text {
                color: #fff;
                font-size: 1.2rem;
                text-align: center;
                font-weight: 500;
                text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            }
        </style>

        <!-- Select2 selects -->
        <div class="mb-3 d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0 font-weight-semibold">
                    Selecciona las opciones deseadas
                </h6>
                <span class="text-muted d-block">Filtros</span>
            </div>
            <button class="btn btn-outline-secondary" id="reset-filtros-btn" type="button">
                <i class="icon-reset mr-1"></i> Restablecer filtros
            </button>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-4">
                                <div class="form-group">
                                    <label>USUARIO</label>
                                    <select class="form-control select-search" id="cliente_id" name="cliente_id">
                                        <option value="0">Selecciona una opción</option>
                                        @foreach ($clientes as $item)
                                            <option value="{{ $item->id }}"
                                                {{ request('cliente_id') == $item->id ? 'selected' : '' }}>
                                                {{ $item->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label>PARCELA</label>
                                    <select class="form-control select-search" id="parcela_id" name="parcela_id">
                                        <option value="">Selecciona una parcela</option>
                                        @foreach ($parcelas as $item)
                                            <option value="{{ $item->id }}"
                                                {{ request('parcela_id') == $item->id ? 'selected' : '' }}>
                                                {{ $item->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label>ZONA DE MANEJO</label>
                                    <select class="form-control select-search" id="zonas" name="zonas_manejo">
                                        <option value="">Selecciona una Zona de Manejo</option>
                                        @foreach ($zonaManejo as $item)
                                            <option value="{{ $item->id }}"
                                                {{ request('zona_manejo_id') == $item->id ? 'selected' : '' }}>
                                                {{ $item->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @if (request('cliente_id') && request('parcela_id') && request('zona_manejo_id'))
                                <div class="col-6" id="input-cultivo">
                                    <div class="form-group">
                                        <label>VARIEDADES</label>
                                        <select class="form-control select-search" id="tipo_cultivo_id_select"
                                            name="tipo_cultivo_id">
                                            <option value="0">Selecciona una opción</option>
                                            @foreach ($tipoCultivo as $item)
                                                <option value="{{ $item->id }}"
                                                    {{ request('tipo_cultivo_id') == $item->id ? 'selected' : '' }}>
                                                    {{ $item->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6" id="input-etapa">
                                    <div class="form-group">
                                        <label>ETAPA FENOLÓGICA</label>
                                        <select class="form-control select-search" id="etapa_fenologica_id_select"
                                            name="etapa_fenologica_id">
                                            <option value="0">Selecciona una opción</option>
                                            @foreach ($etapaFenologica as $item)
                                                <option value="{{ $item->id }}"
                                                    {{ request('etapa_fenologica_id') == $item->id ? 'selected' : '' }}>
                                                    {{ $item->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <!-- /select2 selects -->
        </div>
        <!-- /content area -->
        @if (request('cliente_id') &&
                request('parcela_id') &&
                request('zona_manejo_id') &&
                request('tipo_cultivo_id') &&
                request('etapa_fenologica_id'))
            <div class="row" id="bloque_1">
                <div class="col-md-3">
                    <div class="card card-body border-top-info">
                        <div class="text-center">
                            <h6 class="mb-0 font-weight-semibold">Textura de suelo:</h6>
                            <p class="mb-3" id="textura_de_suelo">
                                @if (isset($bloqueUno) && $bloqueUno)
                                    {{ $bloqueUno['zona_manejo']['tipo_suelo'] }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-body border-top-info">
                        <div class="text-center">
                            <h6 class="mb-0 font-weight-semibold" id="cultivo">Cultivo:</h6>
                            <p class="mb-3" id="tipo_c">
                                @if (isset($bloqueUno) && $bloqueUno)
                                    {{ $bloqueUno['zona_manejo']['tipo_cultivo'] }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-body border-top-info">
                        <div class="text-center">
                            <h6 class="mb-0 font-weight-semibold">Edad del cultivo:</h6>
                            <p class="mb-3" id="edad_del_cultivo">
                                @if (isset($bloqueUno) && $bloqueUno)
                                    {{ $bloqueUno['zona_manejo']['edad_cultivo'] }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-body border-top-info">
                        <div class="text-center">
                            <h6 class="mb-0 font-weight-semibold">Fecha de la última transmisión:</h6>
                            @php
                                $colorUltimaTransmision =
                                    isset($bloqueUno['ultima_transmision']) && $bloqueUno['ultima_transmision'] > 1
                                        ? '#b81702'
                                        : '#0e9c26';
                            @endphp
                            <p class="mb-3" id="ultima_transmicion" style="color: {{ $colorUltimaTransmision }}">
                                <strong>{{ $bloqueUno['estacion']['ultimaTransmision']['created_at'] ?? 'sin datos' }}</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row" id="chartsContainer">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header header-elements-inline">
                            <div class="header-elements">
                                <div class="list-icons">
                                    <a class="list-icons-item" data-action="collapse"></a>
                                    <a class="list-icons-item" data-action="reload"></a>
                                    <a class="list-icons-item" data-action="remove"></a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="form-group row">
                                <div class="col-lg-5">
                                    <h3 style="text-align: center">Atmósfera</h3>

                                    <table class="table table-bordered">
                                        <tr class="success">
                                            <th style="text-align: center;">Temperatura Atmosférica (°C)</th>
                                            <th style="text-align: center;">Humedad Relativa Atmosférica (%)</th>
                                            <th style="text-align: center;">CO<sub>2</sub> Atmosférico (ppm)</th>
                                        </tr>
                                        <tr>
                                            <th id="temperatura" style="text-align: center;">
                                                @if (isset($bloqueUno) && $bloqueUno && isset($bloqueUno['estacion']['ultimaTransmision']['temperatura']))
                                                    {{ number_format($bloqueUno['estacion']['ultimaTransmision']['temperatura'], 1) }}
                                                @endif
                                            </th>
                                            <th id="humedad_relativa" style="text-align: center;">
                                                @if (isset($bloqueUno) && $bloqueUno && isset($bloqueUno['estacion']['ultimaTransmision']['humedad_relativa']))
                                                    {{ number_format($bloqueUno['estacion']['ultimaTransmision']['humedad_relativa'], 1) }}
                                                @endif
                                            </th>
                                            <th id="co2" style="text-align: center;">
                                                @if (isset($bloqueUno) && $bloqueUno && isset($bloqueUno['estacion']['ultimaTransmision']['co2']))
                                                    {{ number_format($bloqueUno['estacion']['ultimaTransmision']['co2'], 1) }}
                                                @endif
                                            </th>
                                        </tr>
                                    </table>

                                </div>
                                <div class="col-lg-7">
                                    <h3 style="text-align: center">Suelo</h3>
                                    <table class="table table-bordered">
                                        <tr class="warning">
                                            <th style="text-align: center;">Humedad del Suelo (%)</th>
                                            <th style="text-align: center;">Conductividad Eléctrica (Ds/m)</th>
                                            <th style="text-align: center;">Potencial de Hidrógeno (pH)</th>
                                            <th style="text-align: center;">Nitrógeno (ppm)</th>
                                            <th style="text-align: center;">Fósforo (ppm)</th>
                                            <th style="text-align: center;">Potasio (ppm)</th>
                                        </tr>
                                        <tr>
                                            <th id="humedad_15" style="text-align: center;">
                                                @if (isset($bloqueUno) && $bloqueUno && isset($bloqueUno['estacion']['ultimaTransmision']['humedad_15']))
                                                    {{ number_format($bloqueUno['estacion']['ultimaTransmision']['humedad_15'], 1) }}
                                                @endif
                                            </th>
                                            <th id="conductividad_electrica" style="text-align: center;">
                                                @if (isset($bloqueUno) && $bloqueUno && isset($bloqueUno['estacion']['ultimaTransmision']['conductividad_electrica']))
                                                    {{ number_format($bloqueUno['estacion']['ultimaTransmision']['conductividad_electrica'], 2) }}
                                                @endif
                                            </th>
                                            <th id="ph" style="text-align: center;">
                                                @if (isset($bloqueUno) && $bloqueUno && isset($bloqueUno['estacion']['ultimaTransmision']['ph']))
                                                    {{ number_format($bloqueUno['estacion']['ultimaTransmision']['ph'], 1) }}
                                                @endif
                                            </th>
                                            <th id="nit" style="text-align: center;">
                                                @if (isset($bloqueUno) && $bloqueUno && isset($bloqueUno['estacion']['ultimaTransmision']['nit']))
                                                    {{ number_format($bloqueUno['estacion']['ultimaTransmision']['nit'], 1) }}
                                                @endif
                                            </th>
                                            <th id="phos" style="text-align: center;">
                                                @if (isset($bloqueUno) && $bloqueUno && isset($bloqueUno['estacion']['ultimaTransmision']['phos']))
                                                    {{ number_format($bloqueUno['estacion']['ultimaTransmision']['phos'], 1) }}
                                                @endif
                                            </th>
                                            <th id="pot" style="text-align: center;">
                                                @if (isset($bloqueUno) && $bloqueUno && isset($bloqueUno['estacion']['ultimaTransmision']['pot']))
                                                    {{ number_format($bloqueUno['estacion']['ultimaTransmision']['pot'], 1) }}
                                                @endif
                                            </th>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row" id="bloque_2">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header header-elements-inline">
                            <h4 class="card-title">Configura el periodo de visualización de datos</h4>
                            <div class="header-elements">
                                <div class="list-icons">
                                    <a class="list-icons-item" data-action="collapse"></a>
                                    <a class="list-icons-item" data-action="reload"></a>
                                    <a class="list-icons-item" data-action="remove"></a>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">

                            <div class="form-group row">
                                <div class="col-lg-4">
                                    <label class="col-form-label col-lg-12">Selecciona un rango</label>
                                    <select class="form-control bg-brown-300 border-brown-300" id="selectPeriodo">
                                        <option value="1" {{ request('periodo') == 1 ? 'selected' : '' }}>Últimas 24
                                            horas</option>
                                        <option value="2" {{ request('periodo') == 2 ? 'selected' : '' }}>Últimas 48
                                            horas</option>
                                        <option value="3" {{ request('periodo') == 3 ? 'selected' : '' }}>Última
                                            semana</option>
                                        <option value="4" {{ request('periodo') == 4 ? 'selected' : '' }}>Últimas 2
                                            semanas</option>
                                        <option value="5" {{ request('periodo') == 5 ? 'selected' : '' }}>Último mes
                                        </option>
                                        <option value="6" {{ request('periodo') == 6 ? 'selected' : '' }}>Último
                                            bimestre</option>
                                        <option value="7" {{ request('periodo') == 7 ? 'selected' : '' }}>Último
                                            semestre</option>
                                        <option value="8" {{ request('periodo') == 8 ? 'selected' : '' }}>Último año
                                        </option>
                                        <option value="9" {{ request('periodo') == 9 ? 'selected' : '' }}>Periodo
                                            personalizado</option>
                                        <option value="10" {{ request('periodo') == 10 ? 'selected' : '' }}>Próximas
                                            24 horas</option>
                                        <option value="11" {{ request('periodo') == 11 ? 'selected' : '' }}>Próximas
                                            48 horas</option>
                                        <option value="12" {{ request('periodo') == 12 ? 'selected' : '' }}>Últimas 24
                                            horas + Próximas 48 horas</option>
                                        <option value="13" {{ request('periodo') == 13 ? 'selected' : '' }}>Últimas 48
                                            horas + Próximas 48 horas</option>
                                        <option value="14" {{ request('periodo') == 14 ? 'selected' : '' }}>Última
                                            semana + Próximas 48 horas</option>
                                    </select>
                                </div>
                                <div class="col-4" id="rango_fechas_container" style="display: none;">
                                    <div class="form-group">
                                        <label class="col-form-label col-lg-12">Selecciona un rango</label>
                                        <div class="input-group">
                                            <span class="input-group-prepend">
                                                <span class="input-group-text"><i class="icon-calendar22"></i></span>
                                            </span>
                                            <input type="text" class="form-control daterange-basic" value=""
                                                id="rango_fechas" name="rango_fechas" />
                                        </div>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="form-group row text-center">
                                        <label class="col-form-label col-lg-12">Exportar mediciones del periodo
                                            seleccionado o
                                            todas las mediciones</label>
                                        <div class="col-6 text-center">
                                            <button class="btn btn-success" onclick="exportarEstacionDato()">
                                                <i class="icon-file-excel mr-2"></i>Exportar periodo
                                            </button>
                                        </div>
                                        <div class="col-6 text-center">
                                            <button class="btn btn-success" onclick="mostrarModalProgreso()">
                                                <i class="icon-file-excel mr-2"></i>Exportar todo
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <ul class="nav nav-tabs nav-tabs-bottom nav-justified">
                                <li class="nav-item">
                                    <a href="#justified-right-icon-tab1" class="nav-link active" data-toggle="tab">
                                        <img src="{{ url('/assets/images/fenologia.jpeg') }}" class="pr-2 img-fluid"
                                            style="width: 40px; object-fit: cover;" alt="">
                                        <span class="h6 mb-0">Fenología</span>
                                    </a>
                                </li>
                                {{-- <li class="nav-item">
                                    <a href="#justified-right-icon-tab2" class="nav-link" data-toggle="tab">
                                        <img src="{{ url('/assets/images/fertilidad.jpeg') }}" class="pr-2 img-fluid"
                                            style="width: 40px; object-fit: cover;" alt="">
                                        <span class="h6 mb-0">Fertilidad</span>
                                    </a>
                                </li> --}}
                                <li class="nav-item">
                                    <a href="#justified-right-icon-tab3" class="nav-link" data-toggle="tab">
                                        <img src="{{ url('/assets/images/nutricion.jpeg') }}" class="pr-2 img-fluid"
                                            style="width: 40px; object-fit: cover;" alt="">
                                        <span class="h6 mb-0">Nutrición</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#justified-right-icon-tab4" class="nav-link" data-toggle="tab">
                                        <img src="{{ url('/assets/images/riego.jpeg') }}" class="pr-2 img-fluid"
                                            style="width: 40px; object-fit: cover;" alt="">
                                        <span class="h6 mb-0">Riego</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#justified-right-icon-tab5" class="nav-link" data-toggle="tab">
                                        <img src="{{ url('/assets/images/plagas.jpeg') }}" class="pr-2 img-fluid"
                                            style="width: 40px; object-fit: cover;" alt="">
                                        <span class="h6 mb-0">Plagas</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#justified-right-icon-tab6" class="nav-link" data-toggle="tab">
                                        <img src="{{ url('/assets/images/enfermedades.jpeg') }}" class="pr-2 img-fluid"
                                            style="width: 40px; object-fit: cover;" alt="">
                                        <span class="h6 mb-0">Enfermedades</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#justified-right-icon-tab7" class="nav-link" data-toggle="tab">
                                        <img src="{{ url('/assets/images/interaccion.jpeg') }}" class="pr-2 img-fluid"
                                            style="width: 40px; object-fit: cover;" alt="">
                                        <span class="h6 mb-0">Interacción de factores</span>
                                    </a>
                                </li>
                                {{-- Creamos un nuevo apartado para el ICP (Índice de Capacidad Productiva) --}}
                                <li class="nav-item">
                                    <a href="#justified-right-icon-tab8" class="nav-link" data-toggle="tab">
                                        <img src="{{ url('/assets/images/nutricion.jpeg') }}" class="pr-2 img-fluid"
                                            style="width: 40px; object-fit: cover;" alt="">
                                        <span class="h6 mb-0">ICP</span>
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                {{-- FENOLOGÍA --}}
                                <div class="tab-pane show active" id="justified-right-icon-tab1">
                                    <div class="card-body">
                                        <ul class="nav nav-pills nav-pills-bordered nav-justified">
                                            <li class="nav-item"><a href="#bordered-justified-pill1"
                                                    class="nav-link active" data-toggle="tab"><span
                                                        class="h6 mb-0 font-weight-bold">Unidades calor / frío</span></a>
                                            </li>
                                            <li class="nav-item"><a href="#bordered-justified-pill2" class="nav-link"
                                                    data-toggle="tab"><span class="h6 mb-0 font-weight-bold">Temperatura
                                                        atmosférica</span></a></li>
                                            <li class="nav-item"><a href="#bordered-justified-pill3" class="nav-link"
                                                    data-toggle="tab"><span
                                                        class="h6 mb-0 font-weight-bold">CO<sub>2</sub>
                                                        atmosférico</span></a></li>
                                            <li class="nav-item"><a href="#bordered-justified-pill4" class="nav-link"
                                                    data-toggle="tab"><span class="h6 mb-0 font-weight-bold">Temperatura
                                                        del suelo</span></a></li>
                                            <li class="nav-item"><a href="#bordered-justified-pill5" class="nav-link"
                                                    data-toggle="tab"><span class="h6 mb-0 font-weight-bold">Velocidad del
                                                        viento</span></a></li>
                                            <li class="nav-item"><a href="#bordered-justified-pill6" class="nav-link"
                                                    data-toggle="tab"><span class="h6 mb-0 font-weight-bold">Presión
                                                        atmosférica</span></a></li>

                                        </ul>

                                        <div class="tab-content">
                                            <div class="tab-pane fade show active" id="bordered-justified-pill1">
                                                {{-- UNUDADES CALOR / FRIO --}}
                                                <div class="row">
                                                    <div class="col-12" style="overflow:auto">
                                                        <table class="table table-bordered">
                                                            <tr>
                                                                <th colspan="5" style="text-align:center">Reporte del
                                                                    periodo seleccionado</th>
                                                            </tr>
                                                            <tr>
                                                                <td
                                                                    style="text-align:center; background-color: #ed3326; color:#fff">
                                                                    Temperatura Máxima</td>
                                                                <td
                                                                    style="text-align:center; background-color: #075ab3; color:#fff">
                                                                    Temperatura Mínima</td>
                                                                <td
                                                                    style="text-align:center; background-color: #c75b08; color:#fff">
                                                                    Amplitud Térmica</td>
                                                                <td
                                                                    style="text-align:center; background-color: #610704; color:#fff">
                                                                    Unidades Calor Totales Acumuladas</td>
                                                                <td
                                                                    style="text-align:center; background-color: #04366b; color:#fff">
                                                                    Unidades Frío Totales Acumuladas</td>
                                                            </tr>
                                                            <tr>
                                                                <td style="text-align:center; color:#ed3326;"
                                                                    id="temp_max">
                                                                    @if (isset($unidadesChart) && $unidadesChart && isset($unidadesChart['resumen']))
                                                                        {{ $unidadesChart['resumen']->temp_max ?? '22.6' }}
                                                                        °C
                                                                    @endif
                                                                </td>
                                                                <td style="text-align:center; color:#075ab3;"
                                                                    id="temp_min">
                                                                    @if (isset($unidadesChart) && $unidadesChart && isset($unidadesChart['resumen']))
                                                                        {{ $unidadesChart['resumen']->temp_min ?? '15.6' }}
                                                                        °C
                                                                    @endif
                                                                </td>
                                                                <td style="text-align:center; color:#c75b08;"
                                                                    id="amplitud">
                                                                    @if (isset($unidadesChart) && $unidadesChart && isset($unidadesChart['resumen']))
                                                                        {{ $unidadesChart['resumen']->amplitud ?? '7' }}
                                                                        °C
                                                                    @endif
                                                                </td>
                                                                <td style="text-align:center; color:#610704;"
                                                                    id="uc">
                                                                    @if (isset($unidadesChart) && $unidadesChart && isset($unidadesChart['resumen']))
                                                                        {{ $unidadesChart['resumen']->uc ?? '9.1' }}
                                                                    @endif
                                                                </td>
                                                                <td style="text-align:center; color:#04366b;"
                                                                    id="uf">
                                                                    @if (isset($unidadesChart) && $unidadesChart && isset($unidadesChart['resumen']))
                                                                        {{ max(0, $unidadesChart['resumen']->uf ?? 0) }}
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div id="barchart_material_frio"
                                                            style="height: 350px; width:100%">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <h3>Desglose de temperaturas del periodo</h3>
                                                        <div style="height: 350px; overflow-y: scroll; ">
                                                            <table class="table table-bordered" id="tabla-desglose">
                                                                <thead>
                                                                    <tr>
                                                                        <th style="text-align: center;"></th>
                                                                        <th colspan="3"
                                                                            style="text-align:center; border:none !important;">
                                                                            <img src="/assets/images/dianoche.png"
                                                                                width="120"
                                                                                style="margin-left: 10px;" />
                                                                        </th>
                                                                        <th colspan="3" style="text-align:center">
                                                                            <img src="/assets/images/dia.png"
                                                                                width="120" />
                                                                        </th>
                                                                        <th colspan="3" style="text-align:center">
                                                                            <img src="/assets/images/noche.png"
                                                                                width="120" />
                                                                        </th>
                                                                        <th colspan="2" style="text-align:center">
                                                                            <img src="/assets/images/UCA.jpeg"
                                                                                width="120" />
                                                                        </th>
                                                                        <th colspan="2" style="text-align:center">
                                                                            <img src="/assets/images/UFRIO.jpeg"
                                                                                width="120" />
                                                                        </th>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="text-align: center;"></th>
                                                                        <th colspan="3" style="text-align: center;">
                                                                            Datos 24 horas C</th>
                                                                        <th colspan="3" style="text-align: center;">
                                                                            Datos Diurnos C</th>
                                                                        <th colspan="3" style="text-align: center;">
                                                                            Datos Nocturnos C</th>
                                                                        <th colspan="2" style="text-align: center;">
                                                                            Unidades Calor</th>
                                                                        <th colspan="2" style="text-align: center;">
                                                                            Unidades Frío</th>
                                                                    </tr>
                                                                    <tr>
                                                                        <th style="text-align: center;">Fecha</th>
                                                                        <th style="text-align: center;">Máxima</th>
                                                                        <th style="text-align: center;">Mínima</th>
                                                                        <th style="text-align: center;">Amplitud</th>
                                                                        <th style="text-align: center;">Máxima</th>
                                                                        <th style="text-align: center;">Mínima</th>
                                                                        <th style="text-align: center;">Amplitud</th>
                                                                        <th style="text-align: center;">Máxima</th>
                                                                        <th style="text-align: center;">Mínima</th>
                                                                        <th style="text-align: center;">Amplitud</th>
                                                                        <th style="text-align: center;">Generadas</th>
                                                                        <th style="text-align: center;">Acumuladas</th>
                                                                        <th style="text-align: center;">Generadas</th>
                                                                        <th style="text-align: center;">Acumuladas</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody id="desglose-body-js">
                                                                    <!-- Se llenará dinámicamente por JS -->
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="tab-pane fade" id="bordered-justified-pill2">
                                                <div class="chart has-fixed-height" id="iframeTemperatura"></div>
                                            </div>

                                            <div class="tab-pane fade" id="bordered-justified-pill3">
                                                <div class="chart has-fixed-height" id="component_grafica_co2"></div>
                                            </div>

                                            <div class="tab-pane fade" id="bordered-justified-pill4">
                                                <div class="chart has-fixed-height"
                                                    id="component_grafica_temperatura_suelo">
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="bordered-justified-pill5">
                                                <div class="chart has-fixed-height"
                                                    id="component_grafica_velocidad_viento"></div>
                                            </div>
                                            <div class="tab-pane fade" id="bordered-justified-pill6">
                                                <div class="chart has-fixed-height"
                                                    id="component_grafica_presion_atmosferica"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- END FENOLOGÍA --}}

                                <div class="tab-pane fade" id="justified-right-icon-tab2">
                                    Food truck fixie locavore, accusamus mcsweeney's marfa nulla single-origin coffee squid
                                    laeggin.
                                </div>

                                <div class="tab-pane fade" id="justified-right-icon-tab3">
                                    <ul class="nav nav-pills nav-pills-bordered nav-justified" id="nutricionTabsBootstrap"
                                        role="tablist">
                                        <li class="nav-item"><a href="#nutricion-tab1" class="nav-link active"
                                                data-toggle="tab" role="tab"><span
                                                    class="h6 mb-0 font-weight-bold">pH</span></a></li>
                                        <li class="nav-item"><a href="#nutricion-tab2" class="nav-link"
                                                data-toggle="tab" role="tab"><span
                                                    class="h6 mb-0 font-weight-bold">Nitrógeno</span></a></li>
                                        <li class="nav-item"><a href="#nutricion-tab3" class="nav-link"
                                                data-toggle="tab" role="tab"><span
                                                    class="h6 mb-0 font-weight-bold">Fósforo</span></a></li>
                                        <li class="nav-item"><a href="#nutricion-tab4" class="nav-link"
                                                data-toggle="tab" role="tab"><span
                                                    class="h6 mb-0 font-weight-bold">Potasio</span></a></li>
                                        <li class="nav-item"><a href="#nutricion-tab5" class="nav-link"
                                                data-toggle="tab" role="tab"><span
                                                    class="h6 mb-0 font-weight-bold">Conductividad Eléctrica</span></a>
                                        </li>
                                    </ul>
                                    <div class="tab-content" id="nutricionTabsContentBootstrap">
                                        <div class="tab-pane fade show active" id="nutricion-tab1" role="tabpanel">
                                            <div class="card-body">
                                                <div id="component_grafica_ph"> ... </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="nutricion-tab2" role="tabpanel">
                                            <div class="card-body">
                                                <div id="component_grafica_nitrogeno"> ... </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="nutricion-tab3" role="tabpanel">
                                            <div class="card-body">
                                                <div id="component_grafica_fosforo"> ... </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="nutricion-tab4" role="tabpanel">
                                            <div class="card-body">
                                                <div id="component_grafica_potasio"> ... </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="nutricion-tab5" role="tabpanel">
                                            <div class="card-body">
                                                <div id="component_grafica_conductividad_electrica"> ... </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="justified-right-icon-tab4">
                                    <ul class="nav nav-pills nav-pills-bordered nav-justified" id="riegoTabsBootstrap"
                                        role="tablist">
                                        <li class="nav-item"><a href="#riego-tab1" class="nav-link active"
                                                data-toggle="tab" role="tab"><span
                                                    class="h6 mb-0 font-weight-bold">Humedad Relativa</span></a></li>
                                        <li class="nav-item"><a href="#riego-tab2" class="nav-link" data-toggle="tab"
                                                role="tab"><span class="h6 mb-0 font-weight-bold">Humedad del
                                                    Suelo</span></a></li>
                                        <li class="nav-item"><a href="#riego-tab3" class="nav-link" data-toggle="tab"
                                                role="tab"><span class="h6 mb-0 font-weight-bold">Precipitación
                                                    Pluvial</span></a></li>
                                    </ul>
                                    <div class="tab-content" id="riegoTabsContentBootstrap">
                                        <div class="tab-pane fade show active" id="riego-tab1" role="tabpanel">
                                            <div class="card-body">
                                                <div id="component_grafica_humedad_relativa">
                                                    <div class="text-center">
                                                        <div class="spinner-border text-primary" role="status">
                                                            <span class="sr-only">Cargando...</span>
                                                        </div>
                                                        <p class="mt-2">Cargando gráfica de humedad relativa...</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="riego-tab2" role="tabpanel">
                                            <div class="card-body">
                                                <div id="component_grafica_humedad_suelo">
                                                    <div class="text-center">
                                                        <div class="spinner-border text-primary" role="status">
                                                            <span class="sr-only">Cargando...</span>
                                                        </div>
                                                        <p class="mt-2">Cargando gráfica de humedad del suelo...</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="riego-tab3" role="tabpanel">
                                            <div class="card-body">
                                                <div id="component_grafica_precipitacion_pluvial">
                                                    <div class="text-center">
                                                        <div class="spinner-border text-primary" role="status">
                                                            <span class="sr-only">Cargando...</span>
                                                        </div>
                                                        <p class="mt-2">Cargando gráficas de precipitación pluvial...</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                {{-- PLAGAS --}}
                                <div class="tab-pane fade" id="justified-right-icon-tab5">
                                    <div id="contenedorPlagas">
                                        <div class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="sr-only">Cargando...</span>
                                            </div>
                                            <p class="mt-2">Cargando información de plagas...</p>
                                        </div>
                                    </div>
                                </div>
                                {{-- END PLAGAS --}}

                                <div class="tab-pane fade" id="justified-right-icon-tab6">
                                    <div id="contenedorEnfermedades">
                                        <div class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="sr-only">Cargando...</span>
                                            </div>
                                            <p class="mt-2">Cargando información de enfermedades...</p>
                                        </div>
                                    </div>
                                </div>

                                {{-- INTERACCIÓN DE FACTORES --}}
                                <div class="tab-pane" id="justified-right-icon-tab7">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h5 class="mb-3">Análisis de Variables y Interacción de Factores</h5>

                                                <div class="alert alert-info mb-3">
                                                    <i class="icon-info22"></i>
                                                    <strong>Instrucciones:</strong> Selecciona una o más variables de
                                                    medición y los tipos de datos que deseas analizar (Máximo, Mínimo,
                                                    Promedio).
                                                    Si seleccionas una sola variable, verás su análisis individual. Si
                                                    seleccionas múltiples variables, también verás las correlaciones entre
                                                    ellas.
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="col-form-label col-lg-12">Variables de medición
                                                                <span class="text-danger">*</span></label>
                                                            <select class="form-control multiselect-select-all-filtering"
                                                                id="variables_medicion" name="variables_medicion[]"
                                                                multiple="multiple" data-fouc>
                                                                <!-- Las opciones se cargarán dinámicamente -->
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label class="col-form-label col-lg-12">Tipos de agrupación
                                                                <span class="text-danger">*</span></label>
                                                            <select class="form-control multiselect-select-all-filtering"
                                                                id="agrupacion" name="agrupacion[]" multiple="multiple"
                                                                data-fouc>
                                                                <option value="max|Máximo">Máximo</option>
                                                                <option value="min|Mínimo">Mínimo</option>
                                                                <option value="avg|Promedio">Promedio</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row mt-3">
                                                    <div class="col-12">
                                                        <button type="button" class="btn btn-primary ml-2"
                                                            id="mostrar-graficas">
                                                            <i class="icon-graph"></i> Mostrar Gráficas
                                                        </button>
                                                        <button type="button" class="btn btn-outline-secondary ml-2"
                                                            id="limpiar-seleccion">
                                                            <i class="icon-cross2"></i> Limpiar selección
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Resultados -->
                                                <div id="resultado-interaccion" class="mt-3"></div>

                                                <!-- Gráficas por variable -->
                                                <div id="graficas-variables" class="mt-4" style="display: none;">
                                                    <h5 class="mb-3">Gráficas por Variable</h5>
                                                    <div id="contenedor-graficas"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Creamos un nuevo apartado para el ICP (Índice de Capacidad Productiva) --}}
                                <div class="tab-pane fade" id="justified-right-icon-tab8">
                                <ul class="nav nav-pills nav-pills-bordered nav-justified" id="tab8TabsBootstrap" role="tablist">
                                    <li class="nav-item">
                                        <a href="#tab8-planta" class="nav-link active" data-toggle="tab" role="tab">
                                            <span class="h6 mb-0 font-weight-bold">Planta</span>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="#tab8-agua" class="nav-link" data-toggle="tab" role="tab">
                                            <span class="h6 mb-0 font-weight-bold">Agua</span>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="#tab8-suelo" class="nav-link" data-toggle="tab" role="tab">
                                            <span class="h6 mb-0 font-weight-bold">Suelo</span>
                                        </a>
                                    </li>

                                    <li class="nav-item">
                                        <a href="#tab8-tiempo" class="nav-link" data-toggle="tab" role="tab">
                                            <span class="h6 mb-0 font-weight-bold">Tiempo Atmosférico</span>
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content" id="tab8TabsContentBootstrap">
                                    <!-- PLANTA -->
                                    <div class="tab-pane fade show active" id="tab8-planta" role="tabpanel">
                                        <div class="card-body">
                                            <div id="component_tab8_planta">
                                                <div class="text-center">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="sr-only">Cargando...</span>
                                                    </div>
                                                    <p class="mt-2">Cargando información de planta...</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- AGUA -->
                                    <div class="tab-pane fade" id="tab8-agua" role="tabpanel">
                                        <div class="card-body">
                                            <div id="component_tab8_agua">
                                                <div class="text-center">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="sr-only">Cargando...</span>
                                                    </div>
                                                    <p class="mt-2">Cargando información de agua...</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- SUELO -->
                                    <div class="tab-pane fade" id="tab8-suelo" role="tabpanel">
                                        <div class="card-body">

                                            <!-- Subtabs internos de SUELO -->
                                            <ul class="nav nav-pills nav-pills-bordered nav-justified mb-3" id="tab8SueloTabs" role="tablist">
                                                <li class="nav-item">
                                                    <a href="#tab8-suelo-fertilidad" class="nav-link active" data-toggle="tab" role="tab">
                                                        <span class="h6 mb-0 font-weight-bold">Fertilidad</span>
                                                    </a>
                                                </li>

                                                <li class="nav-item">
                                                    <a href="#tab8-suelo-correctivos" class="nav-link" data-toggle="tab" role="tab" id="btnTabCorrectivos">
                                                        <span class="h6 mb-0 font-weight-bold">Correctivos</span>
                                                    </a>
                                                </li>
                                            </ul>

                                            <div class="tab-content" id="tab8SueloTabsContent">
                                                <!-- Fertilidad -->
                                                <div class="tab-pane fade show active" id="tab8-suelo-fertilidad" role="tabpanel">
                                                    <div id="component_tab8_suelo_fertilidad">
                                                        <div class="text-center">
                                                            <div class="spinner-border text-primary" role="status">
                                                                <span class="sr-only">Cargando...</span>
                                                            </div>
                                                        <!-- Tabla Fertilidad -->
                                                        <div class="table-responsive mt-3">
                                                                <table class="table table-striped table-bordered mb-0" id="tablaFertilidad">
                                                                    <thead class="thead-light">
                                                                    <tr>
                                                                        <th>Indicador</th>
                                                                        <th>ICP</th>
                                                                        <th>Resultado</th>
                                                                        <th>Ponderacion</th>
                                                                        <th>Restriccion</th>
                                                                        <th>Nivel</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody id="tablaFertilidadBody">
                                                                    <!-- Filas dinámicas -->
                                                                    <tr>
                                                                        <td colspan="6" class="text-center text-muted">
                                                                            Cargando resultados de fertilidad...
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Correctivos -->
                                                <div class="tab-pane fade" id="tab8-suelo-correctivos" role="tabpanel">
                                                    <div id="component_tab8_suelo_correctivos">

                                                        <!-- Select de años (solo visible al estar en Correctivos) -->
                                                        <div class="form-group">
                                                            <label for="selectCorrectivosAnio" class="font-weight-bold mb-1">Año</label>
                                                            <select class="form-control" id="selectCorrectivosAnio">
                                                                <option value="" selected disabled>Selecciona un año</option>
                                                                <option value="2024">2024</option>
                                                                <option value="2025">2025</option>
                                                                <option value="2026">2026</option>
                                                            </select>
                                                            <small class="text-muted">Selecciona el año para cargar la recomendación de correctivos.</small>
                                                        </div>

                                                        <!-- Tabla -->
                                                        <div class="table-responsive mt-3">
                                                            <table class="table table-striped table-bordered mb-0" id="tablaCorrectivos">
                                                                <thead class="thead-light">
                                                                    <tr>
                                                                        <th>Correctivo</th>
                                                                        <th>Cantidad Sugerida</th>
                                                                        <th>Unidades</th>
                                                                        <th>Efecto Esperado</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody id="tablaCorrectivosBody">
                                                                    <!-- Filas dinámicas -->
                                                                    <tr>
                                                                        <td colspan="4" class="text-center text-muted">
                                                                            Selecciona un año para mostrar los correctivos sugeridos.
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>


                                    <!-- TIEMPO ATMOSFÉRICO -->
                                    <div class="tab-pane fade" id="tab8-tiempo" role="tabpanel">
                                        <div class="card-body">
                                            <div id="component_tab8_tiempo_atmosferico">
                                                <div class="text-center">
                                                    <div class="spinner-border text-primary" role="status">
                                                        <span class="sr-only">Cargando...</span>
                                                    </div>
                                                    <p class="mt-2">Cargando información de tiempo atmosférico...</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        @else
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-success">No hay datos para mostrar</div>
                </div>
            </div>
        @endif
    @endsection
    @section('scripts')
        <script>
            // FUNCIÓN PARA ACTIVAR TABS DESDE URL
            function activateTab() {
                const tabActivo = "{{ request('tab') }}";
                const pillActivo = "{{ request('pill') }}";

                console.log('🎯 Activando tabs desde URL:', {
                    tab: tabActivo,
                    pill: pillActivo
                });

                // Remover clases active de todas las tabs
                $('.nav-tabs .nav-link').removeClass('active');
                $('.tab-pane').removeClass('show active');
                $('.nav-pills .nav-link').removeClass('active');
                $('.tab-pane.fade').removeClass('show active');

                // Activar tab padre si existe
                if (tabActivo) {
                    const tabLink = $(`.nav-tabs .nav-link[href="#${tabActivo}"]`);
                    const tabPane = $(`#${tabActivo}`);

                    if (tabLink.length && tabPane.length) {
                        tabLink.addClass('active');
                        tabPane.addClass('show active');
                        console.log('✅ Tab padre activado:', tabActivo);
                    }
                }

                // Activar pill si existe
                if (pillActivo) {
                    const pillLink = $(`.nav-pills .nav-link[href="#${pillActivo}"]`);
                    const pillPane = $(`#${pillActivo}`);

                    if (pillLink.length && pillPane.length) {
                        pillLink.addClass('active');
                        pillPane.addClass('show active');
                        console.log('✅ Pill activado:', pillActivo);
                    }
                }
            }

            // FUNCIÓN PARA CARGAR CONTENIDO DE TABS
            function getTab() {
                const tab = "{{ request('tab') }}";
                const pill = "{{ request('pill') }}";

                console.log('🔍 getTab ejecutándose con:', {
                    tab,
                    pill
                });

                if (tab) {
                    if (tab == 'justified-right-icon-tab1') {

                        if (pill == 'bordered-justified-pill1') {
                            let unidadesFrio = 0;
                            let unidadesCalor = 0;
                            if (window.unidadesData) {
                                unidadesFrio = window.unidadesData.unidadesFrio !== undefined ? window.unidadesData
                                    .unidadesFrio :
                                    0;
                                unidadesCalor = window.unidadesData.unidadesCalor !== undefined ? window.unidadesData
                                    .unidadesCalor : 0;
                            }
                            drawElementsChart(null, {
                                unidadesFrio: unidadesFrio,
                                unidadesCalor: unidadesCalor
                            }, 'barchart_material_frio');
                        } else if (pill == 'bordered-justified-pill2') {
                            console.log('🚀 Cargando gráfica temperatura atmosférica');
                            cargarGraficaTemperaturaAtmosferica();
                        } else if (pill == 'bordered-justified-pill3') {
                            console.log('🚀 Cargando gráfica CO2');
                            cargarGraficaCO2();
                        } else if (pill == 'bordered-justified-pill4') {
                            console.log('🚀 Cargando gráfica temperatura suelo');
                            cargarGraficaTemperaturaSuelo();
                            setTimeout(function() {
                                if (typeof window.cargarGraficasTemperaturaSuelo === 'function') {
                                    window.cargarGraficasTemperaturaSuelo();
                                }
                            }, 200);
                        } else if (pill == 'bordered-justified-pill5') {
                            console.log('🚀 Cargando gráfica velocidad viento');
                            cargarGraficaVelocidadViento();
                        } else if (pill == 'bordered-justified-pill6') {
                            console.log('🚀 Cargando gráfica presión atmosférica');
                            cargarGraficaPresionAtmosferica();
                        }
                    } else if (tab == 'justified-right-icon-tab3') {
                        if (pill == 'nutricion-tab1') {
                            console.log('🚀 Cargando gráfica PH');
                            cargarGraficaPH();
                        } else if (pill == 'nutricion-tab2') {
                            console.log('🚀 Cargando gráfica Nitrógeno');
                            cargarGraficaNitrogeno();
                        } else if (pill == 'nutricion-tab3') {
                            console.log('🚀 Cargando gráfica Fósforo');
                            cargarGraficaFosforo();
                        } else if (pill == 'nutricion-tab4') {
                            console.log('🚀 Cargando gráfica Potasio');
                            cargarGraficaPotasio();
                        } else if (pill == 'nutricion-tab5') {
                            console.log('🚀 Cargando gráfica Conductividad Eléctrica');
                            cargarGraficaConductividadElectrica();
                        }
                    } else if (tab == 'justified-right-icon-tab4') {
                        console.log('🚀 Cargando gráficas de riego');
                        if (pill == 'riego-tab1') {
                            cargarGraficaHumedadRelativa();
                        } else if (pill == 'riego-tab2') {
                            cargarGraficaHumedadSuelo();
                        } else if (pill == 'riego-tab3') {
                            cargarGraficaPrecipitacionPluvial();
                        }
                    } else if (tab == 'justified-right-icon-tab5') {
                        console.log('🚀 Cargando plagas');
                        cargarPlagas();
                    } else if (tab == 'justified-right-icon-tab6') {
                        console.log('🚀 Cargando enfermedades');
                        cargarEnfermedades();
                    } else if (tab == 'justified-right-icon-tab7') {
                        console.log('🚀 Tab de interacción de factores');
                        // No explicit function call here
                    }
                }
            }

            // FUNCIÓN PARA REGISTRAR TABS EN URL
            $(document).ready(function() {
                // Evento para TABS PADRES
                $(document).on('click', '.nav-tabs .nav-link', function() {
                    const href = $(this).attr('href');
                    const tabId = href.replace('#', '');

                    // Actualizar URL con el ID de la tab y ancla fijo
                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.set('tab', tabId);
                    const nuevaURL = window.location.pathname + '?' + urlParams.toString() + '#bloque_2';
                    window.history.replaceState({}, '', nuevaURL);

                    console.log('🔗 URL actualizada con tab y ancla fijo:', tabId);
                });

                // Evento para TABS HIJOS (Pills)
                $(document).on('click', '.nav-pills .nav-link', function() {
                    const href = $(this).attr('href');
                    const pillId = href.replace('#', '');

                    // Actualizar URL con el ID del pill y ancla fijo
                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.set('pill', pillId);
                    const nuevaURL = window.location.pathname + '?' + urlParams.toString() + '#bloque_2';
                    window.history.replaceState({}, '', nuevaURL);

                    console.log('🔗 URL actualizada con pill y ancla fijo:', pillId);
                });
            });
        </script>
        <script>
            // Configurar jQuery para incluir cookies en peticiones AJAX
            $.ajaxSetup({
                xhrFields: {
                    withCredentials: true
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            window.filtros = {
                cliente_id: "{{ request('cliente_id', '') }}",
                parcela_id: "{{ request('parcela_id', '') }}",
                zona_manejo_id: "{{ request('zona_manejo_id', '') }}",
                tipo_cultivo_id: "{{ request('tipo_cultivo_id', '') }}",
                etapa_fenologica_id: "{{ request('etapa_fenologica_id', '') }}",
                periodo: "{{ request('periodo', '1') }}",
                startDate: "{{ request('startDate', '') }}",
                endDate: "{{ request('endDate', '') }}"
            };
        </script>
        <script>
            // Variable para controlar si estamos cargando filtros desde URL
            let cargandoDesdeURL = false;

            // Función para mostrar el cargador con timeout de seguridad
            function mostrarLoader(texto = 'Cargando...') {
                $('#loader').show();
                $('#loader-text').text(texto);
            }

            // Función para ocultar el cargador
            function ocultarLoader() {
                $('#loader').hide();
            }

            // Función para manejar errores y ocultar el cargador
            function manejarError(mensaje = 'Error inesperado') {
                console.error(mensaje);
                ocultarLoader();
                // Opcional: mostrar notificación de error
                if (typeof $.notify !== 'undefined') {
                    $.notify(mensaje, 'error');
                }
            }

            // Función para mostrar notificaciones
            function mostrarNotificacion(mensaje, tipo = 'info') {
                if (typeof $.notify !== 'undefined') {
                    $.notify(mensaje, tipo);
                } else {
                    // Fallback: usar alert simple
                    if (tipo === 'success') {
                        console.log('✅ ' + mensaje);
                    } else if (tipo === 'error') {
                        console.error('❌ ' + mensaje);
                    } else {
                        console.info('ℹ️ ' + mensaje);
                    }
                }
            }

            // Función para actualizar la URL con los parámetros de filtros
            function actualizarURL() {
                console.log('🚀 Función actualizarURL() ejecutándose...');
                console.log('🔒 cargandoDesdeURL:', cargandoDesdeURL);

                // No actualizar URL si estamos cargando desde URL
                if (cargandoDesdeURL) {
                    console.log('⚠️ No actualizando URL porque cargandoDesdeURL es true');
                    return;
                }

                const clienteId = $('#cliente_id').val();
                const parcelaId = $('#parcela_id').val();
                const zonaId = $('#zonas').val();
                const cultivoId = $('#tipo_cultivo_id_select').val();
                const etapaId = $('#etapa_fenologica_id_select').val();
                const periodo = $('#selectPeriodo').val();

                // Obtener fechas personalizadas si el período es 9
                let startDate = '';
                let endDate = '';
                if (periodo === "9") {
                    const rangoFechas = $('#rango_fechas').val();
                    if (rangoFechas) {
                        const fechas = rangoFechas.split(' - ');
                        if (fechas.length === 2) {
                            // Formatear fechas al formato requerido: YYYY-MM-DD HH:mm:ss
                            const fechaInicio = moment(fechas[0].trim(), 'MM/DD/YYYY');
                            const fechaFin = moment(fechas[1].trim(), 'MM/DD/YYYY');

                            if (fechaInicio.isValid() && fechaFin.isValid()) {
                                startDate = fechaInicio.format('YYYY-MM-DD') + ' 00:00:00';
                                endDate = fechaFin.format('YYYY-MM-DD') + ' 23:59:59';
                                console.log('📅 Fechas formateadas correctamente:', startDate, 'a', endDate);
                            } else {
                                // Fallback si el formato no es el esperado
                                startDate = fechas[0].trim();
                                endDate = fechas[1].trim();
                                console.log('⚠️ Usando formato fallback:', startDate, 'a', endDate);
                            }
                        }
                    }
                }

                console.log('📋 Valores de filtros:');
                console.log('  - clienteId:', clienteId);
                console.log('  - parcelaId:', parcelaId);
                console.log('  - zonaId:', zonaId);
                console.log('  - cultivoId:', cultivoId);
                console.log('  - etapaId:', etapaId);
                console.log('  - periodo:', periodo);

                const params = new URLSearchParams();

                // Solo agregar parámetros que tengan valores válidos (no vacíos, null o "0")                

                if (clienteId && clienteId !== "" && clienteId !== "0") params.set('cliente_id', clienteId);
                if (parcelaId && parcelaId !== "" && parcelaId !== "0") params.set('parcela_id', parcelaId);
                if (zonaId && zonaId !== "" && zonaId !== "0") params.set('zona_manejo_id', zonaId);
                if (cultivoId && cultivoId !== "" && cultivoId !== "0") params.set('tipo_cultivo_id', cultivoId);
                if (etapaId && etapaId !== "" && etapaId !== "0") params.set('etapa_fenologica_id', etapaId);
                if (periodo && periodo !== "" && periodo !== "0") params.set('periodo', periodo);

                // Agregar fechas personalizadas si el período es 9
                if (periodo === "9" && startDate && endDate) {
                    params.set('startDate', startDate);
                    params.set('endDate', endDate);
                }

                // 🎯 PRESERVAR PARÁMETROS DE TABS
                const urlParams = new URLSearchParams(window.location.search);
                const tabActivo = urlParams.get('tab');
                const pillActivo = urlParams.get('pill');

                console.log('🔍 DEBUG preservación de tabs:');
                console.log('  - URL actual:', window.location.search);
                console.log('  - tabActivo encontrado:', tabActivo);
                console.log('  - pillActivo encontrado:', pillActivo);

                if (tabActivo) {
                    params.set('tab', tabActivo);
                    console.log('🔒 Preservando tab activo:', tabActivo);
                } else {
                    console.log('⚠️ No se encontró tab activo para preservar');
                }

                if (pillActivo) {
                    params.set('pill', pillActivo);
                    console.log('🔒 Preservando pill activo:', pillActivo);
                } else {
                    console.log('⚠️ No se encontró pill activo para preservar');
                }

                console.log('🔍 Parámetros finales:', params.toString());

                // 🎯 ANCLA FIJO
                const ancla = '#bloque_2';

                const nuevaURL = window.location.pathname + (params.toString() ? '?' + params.toString() : '') + ancla;

                console.log('🔍 Ancla fijo aplicado:', ancla);

                console.log('🔗 Nueva URL generada:', nuevaURL);
                console.log('🔗 URL actual:', window.location.href);

                // Agregar un pequeño delay para que el loader sea visible antes del redirect
                setTimeout(function() {
                    console.log('🔄 Redirigiendo a:', nuevaURL);
                    // Redirigir a la nueva URL
                    window.location.href = nuevaURL;
                }, 500); // 500ms de delay para que el loader sea más visible
            }

            // Función para verificar cuando la página esté completamente cargada
            function paginaCompletamenteCargada() {
                console.log('🎉 Página completamente cargada - Ocultando loader');

                // Ocultar loader definitivamente
                ocultarLoader();

                // Notificar que la página está lista
                mostrarNotificacion('Página cargada completamente', 'success');

                // Disparar evento personalizado
                window.dispatchEvent(new CustomEvent('paginaCompletamenteCargada', {
                    detail: {
                        timestamp: new Date().toISOString(),
                        readyState: document.readyState,
                        imagesCount: document.images.length,
                        scriptsCount: document.scripts.length
                    }
                }));
            }

            // Función para inicializar la verificación de carga completa
            function inicializarVerificacionCarga() {
                console.log('🔍 Inicializando verificación de carga completa...');
                console.log('📊 Estado actual del documento:', document.readyState);

                // Si ya está completamente cargada
                if (document.readyState === 'complete') {
                    console.log('✅ Documento ya está completamente cargado');
                    paginaCompletamenteCargada();
                    return;
                }

                // Si el DOM está listo pero faltan recursos
                if (document.readyState === 'interactive') {
                    console.log('📋 DOM listo, esperando recursos...');
                    window.addEventListener('load', paginaCompletamenteCargada);
                    return;
                }

                // Si aún está cargando
                console.log('⏳ Documento aún cargando, configurando eventos...');

                // Esperar a que el DOM esté listo
                document.addEventListener('DOMContentLoaded', function() {
                    console.log('📋 DOM cargado, esperando recursos...');
                });

                // Esperar a que todo esté completamente cargado
                window.addEventListener('load', paginaCompletamenteCargada);

                // Fallback: verificar cada 100ms por si el evento load no se dispara
                let verificacionInterval = setInterval(function() {
                    if (document.readyState === 'complete') {
                        console.log('✅ Estado completo detectado por intervalo');
                        clearInterval(verificacionInterval);
                        paginaCompletamenteCargada();
                    }
                }, 100);

                // Timeout de seguridad (10 segundos)
                setTimeout(function() {
                    console.log('⏰ Timeout de seguridad alcanzado');
                    clearInterval(verificacionInterval);
                    if (document.readyState !== 'complete') {
                        console.log('⚠️ Forzando ocultación del loader por timeout');
                        paginaCompletamenteCargada();
                    }
                }, 10000);
            }

            // Función para cargar filtros desde la URL al cargar la página
            function cargarFiltrosDesdeURL() {
                cargandoDesdeURL = true; // Activar bandera

                const urlParams = new URLSearchParams(window.location.search);

                // Preseleccionar usuario si existe en la URL
                const clienteId = urlParams.get('cliente_id');
                if (clienteId) {
                    $('#cliente_id').val(clienteId)
                }

                // Preseleccionar parcela si existe en la URL
                const parcelaId = urlParams.get('parcela_id');
                if (parcelaId) {
                    $('#parcela_id').val(parcelaId)
                }

                // Preseleccionar zona si existe en la URL
                const zonaId = urlParams.get('zona_manejo_id');
                if (zonaId) {
                    $('#zonas').val(zonaId)
                }

                // Preseleccionar cultivo si existe en la URL
                const cultivoId = urlParams.get('tipo_cultivo_id');
                if (cultivoId) {
                    $('#tipo_cultivo_id_select').val(cultivoId)
                }

                // Preseleccionar etapa si existe en la URL
                const etapaId = urlParams.get('etapa_fenologica_id');
                if (etapaId) {
                    $('#etapa_fenologica_id_select').val(etapaId)
                }

                // Preseleccionar periodo si existe en la URL
                const periodo = urlParams.get('periodo');
                if (periodo) {
                    $('#selectPeriodo').val(periodo)
                }

                // Preseleccionar rango de fechas si existe en la URL y el periodo es personalizado
                const startDate = urlParams.get('startDate');
                const endDate = urlParams.get('endDate');
                if (periodo === "9" && startDate && endDate) {
                    // Convertir fechas del formato YYYY-MM-DD HH:mm:ss al formato MM/DD/YYYY para el daterangepicker
                    const fechaInicio = moment(startDate, 'YYYY-MM-DD HH:mm:ss');
                    const fechaFin = moment(endDate, 'YYYY-MM-DD HH:mm:ss');

                    if (fechaInicio.isValid() && fechaFin.isValid()) {
                        const rangoFechas = fechaInicio.format('MM/DD/YYYY') + ' - ' + fechaFin.format('MM/DD/YYYY');
                        $('#rango_fechas').val(rangoFechas);
                        console.log('📅 Fechas cargadas desde URL y convertidas:', rangoFechas);
                    } else {
                        // Fallback si el formato no es el esperado
                        const rangoFechas = startDate + ' - ' + endDate;
                        $('#rango_fechas').val(rangoFechas);
                        console.log('⚠️ Usando formato fallback para carga desde URL:', rangoFechas);
                    }
                    $('#rango_fechas_container').show();
                } else if (periodo === "9") {
                    // Si el período es 9 pero no hay fechas, mostrar el contenedor
                    $('#rango_fechas_container').show();
                }

                // Desactivar bandera después de un breve delay
                setTimeout(() => {
                    cargandoDesdeURL = false;
                }, 500);
            }

            $('#cliente_id').change(function() {
                mostrarLoader('Actualizando parcelas del usuario...');
                actualizarURL();
            });

            $('#parcela_id').change(function() {
                mostrarLoader('Actualizando zonas de manejo...');
                actualizarURL();
            });

            $('#zonas').change(function() {
                const zonaId = $(this).val();

                if (!zonaId || zonaId === "" || zonaId === "0") {
                    // Si no hay zona seleccionada, solo actualizar URL
                    mostrarLoader('Limpiando selección...');
                    actualizarURL();
                    return;
                }

                // Verificar si ya están presentes los parámetros de configuración en la URL
                const urlParams = new URLSearchParams(window.location.search);
                const tieneTipoCultivo = urlParams.has('tipo_cultivo_id') && urlParams.get('tipo_cultivo_id') !== "0";
                const tieneEtapa = urlParams.has('etapa_fenologica_id') && urlParams.get('etapa_fenologica_id') !== "0";

                if (tieneTipoCultivo && tieneEtapa) {
                    // Si ya tenemos configuración completa en la URL, solo actualizar
                    mostrarLoader('Cargando datos de la zona seleccionada...');
                    actualizarURL();
                } else {
                    // Si faltan parámetros, intentar cargar configuración guardada
                    mostrarLoader('Buscando configuración guardada para esta zona...');

                    // Cargar configuración guardada primero
                    cargarConfiguracionUsuario(zonaId);
                }
            });

            $('#tipo_cultivo_id_select').change(function() {
                mostrarLoader('Actualizando etapas fenológicas...');
                actualizarURL();

                if (!cargandoDesdeURL) {
                    const zonaId = $('#zonas').val();
                    const cultivoId = $(this).val();
                    const etapaId = $('#etapa_fenologica_id_select').val();

                    if (zonaId && cultivoId && cultivoId !== "0" && etapaId && etapaId !== "0") {
                        mostrarLoader('Guardando configuración...');
                        guardarConfiguracionUsuario(zonaId, cultivoId, etapaId);
                    }
                }
            });

            $('#etapa_fenologica_id_select').change(function() {
                mostrarLoader('Cargando datos del cultivo...');
                actualizarURL();

                if (!cargandoDesdeURL) {
                    const zonaId = $('#zonas').val();
                    const cultivoId = $('#tipo_cultivo_id_select').val();
                    const etapaId = $(this).val();

                    if (zonaId && cultivoId && cultivoId !== "0" && etapaId && etapaId !== "0") {
                        mostrarLoader('Guardando configuración...');
                        guardarConfiguracionUsuario(zonaId, cultivoId, etapaId);
                    }
                }
            });

            $('#selectPeriodo').change(function() {
                const periodo = $(this).val();
                console.log('🔄 Cambio de periodo detectado:', periodo);

                // Mostrar/ocultar contenedor de fechas personalizadas
                if (periodo === "9") {
                    $('#rango_fechas_container').show();
                    // NO actualizar URL hasta que se seleccionen fechas
                    return;
                } else {
                    $('#rango_fechas_container').hide();
                }

                $('#loader').show();
                $('#loader-text').text('Actualizando periodo de visualización...');

                console.log('🚀 Llamando a actualizarURL() desde cambio de periodo');
                actualizarURL();
            });


            // Cargar filtros desde URL cuando la página esté lista
            $(document).ready(function() {
                // Solo mostrar loader si no hay uno ya activo
                if (!$('#loader').is(':visible')) {
                    mostrarLoader('Cargando...');
                }

                // Inicializar verificación de carga completa
                inicializarVerificacionCarga();

                // Configurar evento para el daterange cuando se aplique
                $('#rango_fechas').on('apply.daterangepicker', function(ev, picker) {
                    console.log('📅 Fechas aplicadas:', picker.startDate.format('YYYY-MM-DD'), 'a', picker
                        .endDate.format('YYYY-MM-DD'));

                    $('#loader').show();
                    $('#loader-text').text('Actualizando periodo de visualización...');

                    actualizarURL();
                });

                // Eventos para capturar inicio de carga
                $(window).on('beforeunload', function() {
                    mostrarLoader('Cargando...');
                });

                $(document).ajaxSend(function(event, xhr, settings) {
                    if (!cargandoDesdeURL) {
                        mostrarLoader('Cargando...');
                    }
                });

                $(document).ajaxComplete(function(event, xhr, settings) {
                    // NO ocultar loader aquí, solo cuando la página esté completamente cargada
                });

                // --- ELIMINADO: Timeout global que ocultaba el loader antes de tiempo ---
                // setTimeout(function() {
                //     ocultarLoader();
                // }, 2000);

                // Capturar errores globales y ocultar el cargador
                $(document).ajaxError(function(event, xhr, settings, error) {
                    console.error('Error AJAX:', error);
                    // NO ocultar loader aquí, solo cuando la página esté completamente cargada
                });

                // Capturar errores de JavaScript y ocultar el cargador
                window.addEventListener('error', function(e) {
                    console.error('Error JavaScript:', e.error);
                    // NO ocultar loader aquí, solo cuando la página esté completamente cargada
                });

                // Aplicar colores del semáforo si hay datos disponibles
                @if (isset($bloqueUno) && $bloqueUno && isset($bloqueUno['estacion']['semaforo']))
                    const semaforos = @json($bloqueUno['estacion']['semaforo']);
                    Object.entries(semaforos).forEach(([campo, color]) => {
                        const celda = document.getElementById(campo);
                        if (celda) {
                            celda.style.backgroundColor = color;
                        }
                    });
                @endif

                // Cargar datos de unidades si están disponibles
                @if (isset($unidadesChart))
                    window.unidadesData = @json($unidadesChart);
                @else
                    window.unidadesData = {};
                @endif
                let unidadesFrio = 0;
                let unidadesCalor = 0;
                if (window.unidadesData) {
                    unidadesFrio = window.unidadesData.unidadesFrio !== undefined ? window.unidadesData.unidadesFrio :
                        0;
                    unidadesCalor = window.unidadesData.unidadesCalor !== undefined ? window.unidadesData
                        .unidadesCalor : 0;
                }
                if ($('a[data-toggle="tab"][href="#bordered-justified-pill1"]').hasClass('active')) {
                    drawElementsChart(null, {
                        unidadesFrio: unidadesFrio,
                        unidadesCalor: unidadesCalor
                    }, 'barchart_material_frio');
                }

                // Función helper para convertir valores a números de forma segura
                function safeNumber(value, defaultValue = 0) {
                    if (value === null || value === undefined || value === '') {
                        return defaultValue;
                    }
                    const num = parseFloat(value);
                    return isNaN(num) ? defaultValue : num;
                }

                // Llenar tabla de desglose
                if (window.unidadesData.desglose && window.unidadesData.desglose.length > 0) {
                    let diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sabado'];
                    let tablaBody = $('#desglose-body-js');
                    tablaBody.empty();

                    // Calcular acumulados en orden cronológico (de inicio a fin)
                    // Crear una copia ordenada cronológicamente
                    let desgloseOrdenado = [...window.unidadesData.desglose].sort((a, b) => {
                        return a.fecha.localeCompare(b.fecha); // Orden ascendente (inicio a fin)
                    });

                    // Calcular acumulados en orden cronológico y crear mapa
                    let acumuladoUC = 0;
                    let acumuladoUF = 0;
                    let acumuladosMap = {};

                    desgloseOrdenado.forEach(entry => {
                        acumuladoUC += safeNumber(entry.uc);
                        acumuladoUF += safeNumber(entry.uf);
                        acumuladosMap[entry.fecha] = {
                            uc: acumuladoUC,
                            uf: acumuladoUF
                        };
                    });

                    // Iterar sobre el array original (orden visual) y aplicar acumulados
                    window.unidadesData.desglose.forEach(entry => {
                        // Parsear la fecha correctamente para evitar desfase de zona horaria
                        let partes = entry.fecha.split('-');
                        let fecha = new Date(partes[0], partes[1] - 1, partes[2]);
                        let diaTexto = diasSemana[fecha.getDay()];

                        // Obtener acumulados calculados en orden cronológico
                        let acumulados = acumuladosMap[entry.fecha] || {
                            uc: 0,
                            uf: 0
                        };

                        let fila = `
                                <tr>
                                <td style="text-align: center;">${diaTexto} ${entry.fecha}</td>
                                <td style="text-align: center;">${safeNumber(entry.max).toFixed(2)} °C</td>
                                <td style="text-align: center;">${safeNumber(entry.min).toFixed(2)} °C</td>
                                <td style="text-align: center;">${safeNumber(entry.amp).toFixed(2)} °C</td>
                                <td style="text-align: center;">${safeNumber(entry.max_diurna).toFixed(2)} °C</td>
                                <td style="text-align: center;">${safeNumber(entry.min_diurna).toFixed(2)} °C</td>
                                <td style="text-align: center;">${safeNumber(entry.amp_diurna).toFixed(2)} °C</td>
                                <td style="text-align: center;">${safeNumber(entry.max_nocturna).toFixed(2)} °C</td>
                                <td style="text-align: center;">${safeNumber(entry.min_nocturna).toFixed(2)} °C</td>
                                <td style="text-align: center;">${safeNumber(entry.amp_nocturna).toFixed(2)} °C</td>
                                <td style="text-align: center;">${safeNumber(entry.uc).toFixed(2)}</td>
                                <td style="text-align: center;">${acumulados.uc.toFixed(2)}</td>
                                <td style="text-align: center;">${safeNumber(entry.uf).toFixed(2)}</td>
                                <td style="text-align: center;">${acumulados.uf.toFixed(2)}</td>
                                </tr>`;
                        tablaBody.append(fila);
                    });
                }

                // --- NUEVO: Invocar carga de configuración si hay zona pero faltan los otros parámetros ---
                const urlParams = new URLSearchParams(window.location.search);
                const zonaId = urlParams.get('zona_manejo_id');
                const tieneTipoCultivo = urlParams.has('tipo_cultivo_id') && urlParams.get('tipo_cultivo_id') !== "0";
                const tieneEtapa = urlParams.has('etapa_fenologica_id') && urlParams.get('etapa_fenologica_id') !== "0";

                if (zonaId && (!tieneTipoCultivo || !tieneEtapa)) {
                    cargarConfiguracionUsuario(zonaId);
                }
            });

            function drawElementsChart(escala, datos, container) {
                google.charts.setOnLoadCallback(function() {
                    var data = google.visualization.arrayToDataTable([
                        ['Element', '', {
                            role: 'style'
                        }],
                        ['Unidades frío', datos.unidadesFrio, '#02d8f0'],
                        ['Unidades calor', datos.unidadesCalor, '#ed4209']
                    ]);

                    // Calcular el valor máximo dinámicamente
                    var maxValue = Math.max(datos.unidadesFrio, datos.unidadesCalor);
                    // Agregar un margen del 20% para mejor visualización
                    var maxAxis = Math.ceil(maxValue * 1.2);

                    var options = {
                        pieHole: 0.4,
                        hAxis: {
                            viewWindowMode: 'explicit',
                            viewWindow: {
                                max: maxAxis,
                                min: 0
                            }
                        },
                        legend: {
                            position: 'none'
                        }
                    };

                    var chart = new google.visualization.BarChart(document.getElementById(container));
                    chart.draw(data, options);
                });
            }

            // Dibuja la gráfica cada vez que el tab de Unidades calor/frío se activa
            $(document).on('shown.bs.tab', 'a[data-toggle="tab"][href="#bordered-justified-pill1"]', function() {
                let unidadesFrio = 0;
                let unidadesCalor = 0;
                if (window.unidadesData) {
                    unidadesFrio = window.unidadesData.unidadesFrio !== undefined ? window.unidadesData.unidadesFrio :
                        0;
                    unidadesCalor = window.unidadesData.unidadesCalor !== undefined ? window.unidadesData
                        .unidadesCalor : 0;
                }
                drawElementsChart(null, {
                    unidadesFrio: unidadesFrio,
                    unidadesCalor: unidadesCalor
                }, 'barchart_material_frio');
            });
        </script>
        <script>
            // Función para cargar el contenido de plagas
            function cargarPlagas() {
                let zonaId = window.filtros.zona_manejo_id;
                let cultivoId = window.filtros.tipo_cultivo_id;
                let periodo = window.filtros.periodo;
                let startDate = window.filtros.startDate;
                let endDate = window.filtros.endDate;

                if (zonaId && cultivoId) {
                    // Mostrar loader
                    mostrarLoader('Cargando información de plagas...');

                    $('#contenedorPlagas').html(`
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando información de plagas...</p>
                        </div>
                    `);

                    // Primero cargar el componente HTML
                    $.get("{{ route('component_plagas_graficas') }}", {
                        zonaManejoId: zonaId,
                        tipoCultivoId: cultivoId,
                        periodo: periodo,
                        startDate: startDate,
                        endDate: endDate
                    }, function(html) {
                        $('#contenedorPlagas').html(html);
                        setTimeout(function() {
                            const scripts = $('#contenedorPlagas').find('script');
                            scripts.each(function() {
                                if ($(this).html().trim()) {
                                    eval($(this).html());
                                }
                            });
                            if (window.plagasIds && window.semaforosData && window.GaugeCustomFactory) {
                                window.plagasIds.forEach(function(id) {
                                    var semaforo = window.semaforosData[id];
                                    if (semaforo) {
                                        var containerId = 'gauge_custom_' + id;
                                        window.GaugeCustomFactory.create(
                                            containerId,
                                            semaforo.porcentaje,
                                            semaforo.etapa,
                                            semaforo.color
                                        );
                                    }
                                });
                            }
                            // Ocultar loader después de cargar
                            ocultarLoader();
                        }, 200);
                    }).fail(function() {
                        $('#contenedorPlagas').html(`
                            <div class="alert alert-danger">
                                Error al cargar la información de plagas. Por favor, intenta nuevamente.
                            </div>
                        `);
                        // Ocultar loader en caso de error
                        ocultarLoader();
                    });
                } else {
                    $('#contenedorPlagas').html(`
                        <div class="alert alert-info">
                            Selecciona una zona de manejo y un cultivo para ver las plagas.
                        </div>
                    `);
                }
            }

            // Cargar plagas cuando se haga clic en el tab
            $(document).on('shown.bs.tab', 'a[data-toggle="tab"][href="#justified-right-icon-tab5"]', function() {
                cargarPlagas();
            });

            // Función para cargar el componente de enfermedades
            function cargarEnfermedades() {
                let tipoCultivoId = window.filtros.tipo_cultivo_id;
                let periodo = window.filtros.periodo;
                let startDate = window.filtros.startDate;
                let endDate = window.filtros.endDate;
                let zonaId = window.filtros.zona_manejo_id;

                if (tipoCultivoId && zonaId) {
                    // Mostrar loader
                    mostrarLoader('Cargando información de enfermedades...');

                    $('#contenedorEnfermedades').html(`
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando información de enfermedades...</p>
                        </div>
                    `);

                    // Primero cargar el componente HTML
                    $.get("{{ route('component_enfermedades') }}", {
                        tipo_cultivo_id: tipoCultivoId,
                        periodo: periodo,
                        zona_manejo_id: zonaId,
                        startDate: startDate,
                        endDate: endDate
                    }, function(html) {
                        $('#contenedorEnfermedades').html(html);

                        // Luego obtener los datos JSON de la API
                        $.get("{{ route('api.enfermedades.tipo_cultivo.datos', ['tipoCultivoId' => ':tipoCultivoId']) }}"
                            .replace(':tipoCultivoId', tipoCultivoId), {
                                zona_manejo_id: zonaId,
                                periodo: periodo,
                                startDate: startDate,
                                endDate: endDate
                            },
                            function(response) {
                                console.log('Datos de enfermedades obtenidos:', response);

                                if (response.success && response.data) {
                                    // Cargar gráficas para cada enfermedad
                                    cargarGraficasEnfermedades(response.data);
                                } else {
                                    console.error('Error en respuesta de API:', response.message);
                                }

                                // Ocultar loader
                                ocultarLoader();
                            }).fail(function(xhr, status, error) {
                            console.error('Error al obtener datos de API:', error);
                            console.error('Response:', xhr.responseText);
                            // Ocultar loader en caso de error
                            ocultarLoader();
                        });

                    }).fail(function() {
                        $('#contenedorEnfermedades').html(`
                            <div class="alert alert-danger">
                                Error al cargar la información de enfermedades. Por favor, intenta nuevamente.
                            </div>
                        `);
                        // Ocultar loader en caso de error
                        ocultarLoader();
                    });
                } else {
                    $('#contenedorEnfermedades').html(`
                        <div class="alert alert-info">
                            Selecciona un tipo de cultivo y zona de manejo para ver las enfermedades.
                        </div>
                    `);
                }
            }

            // Función para cargar gráficas de enfermedades con datos de la API
            function cargarGraficasEnfermedades(enfermedades) {
                console.log('Cargando gráficas para enfermedades:', enfermedades);

                // Verificar que EchartsColumnsWaterfallsEnfermedades esté disponible
                if (typeof window.EchartsColumnsWaterfallsEnfermedades === 'undefined') {
                    console.error('EchartsColumnsWaterfallsEnfermedades no está disponible');
                    return;
                }

                // Esperar un poco para asegurar que el DOM esté completamente cargado
                setTimeout(function() {
                    console.log('Iniciando carga de gráficas después del delay...');

                    // Cargar gráfica para cada enfermedad
                    enfermedades.forEach(function(enfermedadData, index) {

                        // Usar la función updateData con los datos de la API y las fechas
                        window.EchartsColumnsWaterfallsEnfermedades.updateData(
                            enfermedadData.porcentajeSinRiesgo,
                            enfermedadData.porcentajeBajo,
                            enfermedadData.porcentajeAlto,
                            enfermedadData.totalGeneral,
                            enfermedadData.enfermedad_id,
                            enfermedadData.fechas
                        );
                    });
                }, 500); // Esperar 500ms para que el DOM esté listo
            }

            // Cargar enfermedades cuando se haga clic en el tab
            $(document).on('shown.bs.tab', 'a[data-toggle="tab"][href="#justified-right-icon-tab6"]', function() {
                cargarEnfermedades();
            });

            // Función para cargar el componente de gráfica de temperatura atmosférica
            function cargarGraficaTemperaturaAtmosferica() {
                let zonaId = window.filtros.zona_manejo_id;
                let periodo = window.filtros.periodo;
                let startDate = window.filtros.startDate;
                let endDate = window.filtros.endDate;
                let tipoCultivoId = window.filtros.tipo_cultivo_id;
                let etapaFenologicaId = window.filtros.etapa_fenologica_id;

                if (zonaId) {
                    // Mostrar loader
                    mostrarLoader('Cargando gráfica de temperatura atmosférica...');

                    $('#iframeTemperatura').html(`
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando gráfica de temperatura atmosférica...</p>
                        </div>
                    `);

                    let url = "{{ route('component_grafica_temperatura_atmosferica', ['zonaManejoId' => ':zonaId']) }}"
                        .replace(':zonaId', zonaId) +
                        '?periodo=' + periodo +
                        '&startDate=' + startDate +
                        '&endDate=' + endDate +
                        '&tipo_cultivo_id=' + tipoCultivoId +
                        '&etapa_fenologica_id=' + etapaFenologicaId;

                    $.get(url, function(html) {
                        $('#iframeTemperatura').html(html);
                        setTimeout(function() {
                            if (typeof window.cargarGraficasTemperaturaAtmosferica === 'function') {
                                window.cargarGraficasTemperaturaAtmosferica();
                            } else {
                                console.error('No se encontró la función cargarGraficasTemperaturaAtmosferica');
                            }
                            // Ocultar loader después de cargar
                            ocultarLoader();
                        }, 200);
                    }).fail(function() {
                        $('#iframeTemperatura').html(`
                            <div class="alert alert-danger">
                                Error al cargar la gráfica de temperatura atmosférica.
                            </div>
                        `);
                        // Ocultar loader en caso de error
                        ocultarLoader();
                    });
                } else {
                    $('#iframeTemperatura').html(`
                        <div class="alert alert-info">
                            Selecciona una zona de manejo para ver la gráfica de temperatura atmosférica.
                        </div>
                    `);
                }
            }

            // Cargar gráfica de temperatura cuando se haga clic en el tab
            $(document).on('shown.bs.tab', 'a[data-toggle="tab"][href="#bordered-justified-pill2"]', function() {
                cargarGraficaTemperaturaAtmosferica();
            });

            // Función para cargar el componente de gráfica de CO2
            function cargarGraficaCO2() {
                let zonaId = window.filtros.zona_manejo_id;
                let periodo = window.filtros.periodo;
                let startDate = window.filtros.startDate;
                let endDate = window.filtros.endDate;
                let tipoCultivoId = window.filtros.tipo_cultivo_id;
                let etapaFenologicaId = window.filtros.etapa_fenologica_id;

                if (zonaId) {
                    // Mostrar loader
                    mostrarLoader('Cargando gráfica de CO2...');
                    $('#component_grafica_co2').html(`
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando gráfica de CO2...</p>
                        </div>
                    `);
                    let url = "{{ route('component_grafica_co2', ['zonaManejoId' => ':zonaId']) }}"
                        .replace(':zonaId', zonaId) +
                        '?periodo=' + periodo +
                        '&startDate=' + startDate +
                        '&endDate=' + endDate +
                        '&tipo_cultivo_id=' + tipoCultivoId +
                        '&etapa_fenologica_id=' + etapaFenologicaId;

                    $.get(url, function(html) {
                        $('#component_grafica_co2').html(html);
                        setTimeout(function() {
                            if (typeof window.cargarGraficasCO2 === 'function') {
                                window.cargarGraficasCO2();
                            }
                            // Ocultar loader después de cargar
                            ocultarLoader();
                        }, 200);
                    }).fail(function(xhr, status, error) {
                        $('#component_grafica_co2').html(`
                            <div class="alert alert-danger">
                                Error al cargar la gráfica de CO2.<br>
                                Status: ${status}<br>
                                Error: ${error}
                            </div>
                        `);
                        // Ocultar loader en caso de error
                        ocultarLoader();
                    });
                } else {
                    $('#component_grafica_co2').html(`
                        <div class="alert alert-info">
                            Selecciona una zona de manejo para ver la gráfica de CO2.
                        </div>
                    `);
                }
            }

            // Cargar gráfica de CO2 cuando se haga clic en el tab
            $(document).on('shown.bs.tab', 'a[data-toggle="tab"][href="#bordered-justified-pill3"]', function() {
                cargarGraficaCO2();
            });

            // Función para cargar el componente de gráfica de velocidad del viento
            function cargarGraficaVelocidadViento() {
                let zonaId = window.filtros.zona_manejo_id;
                let periodo = window.filtros.periodo;
                let startDate = window.filtros.startDate;
                let endDate = window.filtros.endDate;
                let tipoCultivoId = window.filtros.tipo_cultivo_id;
                let etapaFenologicaId = window.filtros.etapa_fenologica_id;

                if (zonaId) {
                    // Mostrar loader
                    mostrarLoader('Cargando gráfica de velocidad del viento...');
                    $('#component_grafica_velocidad_viento').html(`
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando gráfica de velocidad del viento...</p>
                        </div>
                    `);
                    let url = "{{ route('component_grafica_velocidad_viento', ['zonaManejoId' => ':zonaId']) }}"
                        .replace(':zonaId', zonaId) +
                        '?periodo=' + periodo +
                        '&startDate=' + startDate +
                        '&endDate=' + endDate +
                        '&tipo_cultivo_id=' + tipoCultivoId +
                        '&etapa_fenologica_id=' + etapaFenologicaId;

                    $.get(url, function(html) {
                        $('#component_grafica_velocidad_viento').html(html);
                        setTimeout(function() {
                            if (typeof window.cargarGraficasVelocidadViento === 'function') {
                                window.cargarGraficasVelocidadViento();
                            }
                            // Ocultar loader después de cargar
                            ocultarLoader();
                        }, 200);
                    }).fail(function(xhr, status, error) {
                        $('#component_grafica_velocidad_viento').html(`
                            <div class="alert alert-danger">
                                Error al cargar la gráfica de velocidad del viento.<br>
                                Status: ${status}<br>
                                Error: ${error}
                            </div>
                        `);
                        // Ocultar loader en caso de error
                        ocultarLoader();
                    });
                } else {
                    $('#component_grafica_velocidad_viento').html(`
                        <div class="alert alert-info">
                            Selecciona una zona de manejo para ver la gráfica de velocidad del viento.
                        </div>
                    `);
                }
            }

            // Cargar gráfica de velocidad del viento cuando se haga clic en el tab
            $(document).on('shown.bs.tab', 'a[data-toggle="tab"][href="#bordered-justified-pill5"]', function() {
                cargarGraficaVelocidadViento();
            });

            // Función para cargar el componente de gráfica de velocidad del viento
            function cargarGraficaPresionAtmosferica() {
                let zonaId = window.filtros.zona_manejo_id;
                let periodo = window.filtros.periodo;
                let startDate = window.filtros.startDate;
                let endDate = window.filtros.endDate;
                let tipoCultivoId = window.filtros.tipo_cultivo_id;
                let etapaFenologicaId = window.filtros.etapa_fenologica_id;

                if (zonaId) {
                    // Mostrar loader
                    mostrarLoader('Cargando gráfica de presión atmosférica...');
                    $('#component_grafica_presion_atmosferica').html(`
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando gráfica de presión atmosférica...</p>
                        </div>
                    `);
                    let url = "{{ route('component_grafica_presion_atmosferica', ['zonaManejoId' => ':zonaId']) }}"
                        .replace(':zonaId', zonaId) +
                        '?periodo=' + periodo +
                        '&startDate=' + startDate +
                        '&endDate=' + endDate +
                        '&tipo_cultivo_id=' + tipoCultivoId +
                        '&etapa_fenologica_id=' + etapaFenologicaId;

                    $.get(url, function(html) {
                        $('#component_grafica_presion_atmosferica').html(html);
                        setTimeout(function() {
                            if (typeof window.cargarGraficasPresionAtmosferica === 'function') {
                                window.cargarGraficasPresionAtmosferica();
                            }
                            // Ocultar loader después de cargar
                            ocultarLoader();
                        }, 200);
                    }).fail(function(xhr, status, error) {
                        $('#component_grafica_presion_atmosferica').html(`
                            <div class="alert alert-danger">
                                Error al cargar la gráfica de presión atmosférica.<br>
                                Status: ${status}<br>
                                Error: ${error}
                            </div>
                        `);
                        // Ocultar loader en caso de error
                        ocultarLoader();
                    });
                } else {
                    $('#component_grafica_presion_atmosferica').html(`
                        <div class="alert alert-info">
                            Selecciona una zona de manejo para ver la gráfica de presión atmosférica.
                        </div>
                    `);
                }
            }

            // Cargar gráfica de velocidad del viento cuando se haga clic en el tab
            $(document).on('shown.bs.tab', 'a[data-toggle="tab"][href="#bordered-justified-pill6"]', function() {
                cargarGraficaPresionAtmosferica();
            });
        </script>
        <script>
            let sueloCargado = false;

            function cargarGraficaTemperaturaSuelo() {
                if (sueloCargado) {
                    return;
                }
                sueloCargado = true;

                let zonaId = window.filtros.zona_manejo_id;
                let periodo = window.filtros.periodo;
                let startDate = window.filtros.startDate;
                let endDate = window.filtros.endDate;
                let tipoCultivoId = window.filtros.tipo_cultivo_id;
                let etapaFenologicaId = window.filtros.etapa_fenologica_id;

                if (zonaId) {
                    // Mostrar loader
                    mostrarLoader('Cargando gráfica de temperatura del suelo...');

                    $('#component_grafica_temperatura_suelo').html(`
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando gráfica de temperatura del suelo...</p>
                        </div>
                    `);

                    let url = "{{ route('component_grafica_temperatura_suelo', ['zonaManejoId' => ':zonaId']) }}"
                        .replace(':zonaId', zonaId) +
                        '?periodo=' + periodo +
                        '&startDate=' + startDate +
                        '&endDate=' + endDate +
                        '&tipo_cultivo_id=' + tipoCultivoId +
                        '&etapa_fenologica_id=' + etapaFenologicaId;

                    $.get(url, function(html) {
                        $('#component_grafica_temperatura_suelo').html(html);
                        setTimeout(function() {
                            if (typeof window.cargarGraficaTemperaturaSuelo === 'function') {
                                window.cargarGraficaTemperaturaSuelo();
                            }
                            // Ocultar loader después de cargar
                            ocultarLoader();
                        }, 200);
                    }).fail(function(xhr, status, error) {
                        $('#component_grafica_temperatura_suelo').html(`
                            <div class="alert alert-danger">
                                Error al cargar la gráfica de temperatura del suelo.<br>
                                Status: ${status}<br>
                                Error: ${error}
                            </div>
                        `);
                        // Ocultar loader en caso de error
                        ocultarLoader();
                    });
                } else {
                    $('#component_grafica_temperatura_suelo').html(`
                        <div class="alert alert-info">
                            Selecciona una zona de manejo para ver la gráfica de temperatura del suelo.
                        </div>
                    `);
                }
            }

            // Evento tab para cargar la gráfica de suelo
            $(document).on('shown.bs.tab', 'a[data-toggle="tab"][href="#bordered-justified-pill4"]', function() {
                cargarGraficaTemperaturaSuelo();
                // Si existe la función global para la gráfica de estrés, ejecútala
                setTimeout(function() {
                    if (typeof window.cargarGraficasTemperaturaSuelo === 'function') {
                        window.cargarGraficasTemperaturaSuelo();
                    }
                }, 200);
            });

            // Resetear la bandera cuando cambian los filtros
            $('#zonas, #selectPeriodo, #rango_fechas, #tipo_cultivo_id_select, #etapa_fenologica_id_select').on('change',
                function() {
                    sueloCargado = false;
                });
        </script>
        <script>
            // Función para cargar configuración guardada del usuario
            function cargarConfiguracionUsuario(zonaId) {
                mostrarLoader('Verificando configuración guardada...');

                $.ajax({
                    url: '/user-settings',
                    method: 'GET',
                    data: {
                        zona_manejo_id: zonaId
                    },
                    success: function(response) {
                        if (response && response.zona_manejo_id && response.tipo_cultivo_id && response
                            .etapa_fenologica_id) {
                            // Configuración completa encontrada
                            mostrarLoader('Configuración encontrada, aplicando valores guardados...');
                            // NO ocultarLoader aquí ni después
                            setTimeout(function() {
                                actualizarURLConConfiguracion(zonaId, response.tipo_cultivo_id, response
                                    .etapa_fenologica_id);
                            }, 500);
                        } else {
                            // No hay configuración guardada o está incompleta
                            mostrarLoader('No se encontró configuración guardada, actualizando URL...');
                            mostrarNotificacion('No hay configuración guardada para esta zona', 'info');
                            // Actualizar URL solo con la zona seleccionada
                            setTimeout(function() {
                                actualizarURLConConfiguracion(zonaId, null, null);
                            }, 500);
                        }
                    },
                    error: function(xhr, status, error) {
                        mostrarLoader('No se encontró configuración guardada, actualizando URL...');
                        mostrarNotificacion('No hay configuración guardada para esta zona', 'info');
                        // Actualizar URL solo con la zona seleccionada
                        setTimeout(function() {
                            actualizarURLConConfiguracion(zonaId, null, null);
                        }, 500);
                    }
                });
            }

            // Función para actualizar URL con configuración cargada
            function actualizarURLConConfiguracion(zonaId, cultivoId, etapaId) {
                // Activar bandera para evitar recursión
                console.log('Actualizando URL con configuración: zonaId=' + zonaId + ', cultivoId=' + cultivoId + ', etapaId=' +
                    etapaId);
                cargandoDesdeURL = true;

                // Obtener todos los parámetros actuales de la URL
                const urlParams = new URLSearchParams(window.location.search);

                // Conservar todos los parámetros existentes
                const params = new URLSearchParams();
                for (const [key, value] of urlParams.entries()) {
                    params.set(key, value);
                }

                // Siempre agregar la zona de manejo
                if (zonaId && zonaId !== "" && zonaId !== "0") {
                    params.set('zona_manejo_id', zonaId);
                }

                // Solo agregar los nuevos parámetros si no existen ya y no son null
                if (cultivoId && cultivoId !== "" && cultivoId !== "0" && !params.has('tipo_cultivo_id')) {
                    params.set('tipo_cultivo_id', cultivoId);
                }

                if (etapaId && etapaId !== "" && etapaId !== "0" && !params.has('etapa_fenologica_id')) {
                    params.set('etapa_fenologica_id', etapaId);
                }

                // Asegurar que el periodo esté presente (por defecto 1 si no existe)
                if (!params.has('periodo')) {
                    params.set('periodo', '1');
                }

                const nuevaURL = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                console.log('Nueva URL construida: ' + nuevaURL);
                // Recargar la página con los parámetros actualizados
                window.location.href = nuevaURL;
                mostrarLoader('Recargando página con configuración aplicada...');
            }

            // Función para guardar configuración del usuario
            function guardarConfiguracionUsuario(zonaId, cultivoId, etapaId) {
                mostrarLoader('Guardando configuración para futuras visitas...');

                $.ajax({
                    url: '/user-settings',
                    method: 'POST',
                    data: {
                        zona_manejo_id: zonaId,
                        tipo_cultivo_id: cultivoId,
                        etapa_fenologica_id: etapaId
                    },
                    success: function(response) {
                        if (response.success) {
                            mostrarLoader('Configuración guardada correctamente');
                            mostrarNotificacion('Configuración guardada para futuras visitas', 'success');
                        } else {
                            mostrarLoader('Error al guardar configuración');
                            mostrarNotificacion('Error al guardar configuración', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        mostrarLoader('Error al guardar configuración');
                        mostrarNotificacion('Error al guardar configuración', 'error');
                    }
                });
            }
        </script>
        <script>
            // --- HUMEDAD RELATIVA EN RIEGO ---
            function cargarGraficaHumedadRelativa() {
                let zonaId = window.filtros.zona_manejo_id;
                let periodo = window.filtros.periodo;
                let startDate = window.filtros.startDate;
                let endDate = window.filtros.endDate;
                let tipoCultivoId = window.filtros.tipo_cultivo_id;
                let etapaFenologicaId = window.filtros.etapa_fenologica_id;

                if (zonaId) {
                    mostrarLoader('Cargando gráfica de humedad relativa...');
                    $('#component_grafica_humedad_relativa').html(`
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando gráfica de humedad relativa...</p>
                        </div>
                    `);

                    let url = "{{ route('component_grafica_humedad_relativa', ['zonaManejoId' => ':zonaId']) }}"
                        .replace(':zonaId', zonaId) +
                        '?periodo=' + periodo +
                        '&startDate=' + startDate +
                        '&endDate=' + endDate +
                        '&tipo_cultivo_id=' + tipoCultivoId +
                        '&etapa_fenologica_id=' + etapaFenologicaId;

                    $.get(url, function(html) {
                        $('#component_grafica_humedad_relativa').html(html);
                        setTimeout(function() {
                            if (typeof window.cargarGraficasHumedadRelativa === 'function') {
                                window.cargarGraficasHumedadRelativa();
                            }
                            ocultarLoader();
                        }, 200);
                    }).fail(function() {
                        $('#component_grafica_humedad_relativa').html(`
                            <div class="alert alert-danger">
                                Error al cargar la gráfica de humedad relativa.
                            </div>
                        `);
                        ocultarLoader();
                    });
                } else {
                    $('#component_grafica_humedad_relativa').html(`
                        <div class="alert alert-info">
                            Selecciona una zona de manejo para ver la gráfica de humedad relativa.
                        </div>
                    `);
                }
            }

            // --- HUMEDAD DEL SUELO EN RIEGO ---
            function cargarGraficaHumedadSuelo() {
                let zonaId = window.filtros.zona_manejo_id;
                let periodo = window.filtros.periodo;
                let startDate = window.filtros.startDate;
                let endDate = window.filtros.endDate;
                let tipoCultivoId = window.filtros.tipo_cultivo_id;
                let etapaFenologicaId = window.filtros.etapa_fenologica_id;

                if (zonaId) {
                    mostrarLoader('Cargando gráfica de humedad del suelo...');
                    $('#component_grafica_humedad_suelo').html(`
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando gráfica de humedad del suelo...</p>
                        </div>
                    `);

                    let url = "{{ route('component_grafica_humedad_suelo', ['zonaManejoId' => ':zonaId']) }}"
                        .replace(':zonaId', zonaId) +
                        '?periodo=' + periodo +
                        '&startDate=' + startDate +
                        '&endDate=' + endDate +
                        '&tipo_cultivo_id=' + tipoCultivoId +
                        '&etapa_fenologica_id=' + etapaFenologicaId;

                    $.get(url, function(html) {
                        $('#component_grafica_humedad_suelo').html(html);
                        setTimeout(function() {
                            if (typeof window.cargarGraficasHumedadSuelo === 'function') {
                                window.cargarGraficasHumedadSuelo();
                            }
                            ocultarLoader();
                        }, 200);
                    }).fail(function() {
                        $('#component_grafica_humedad_suelo').html(`
                            <div class="alert alert-danger">
                                Error al cargar la gráfica de humedad del suelo.
                            </div>
                        `);
                        ocultarLoader();
                    });
                } else {
                    $('#component_grafica_humedad_suelo').html(`
                        <div class="alert alert-info">
                            Selecciona una zona de manejo para ver la gráfica de humedad del suelo.
                        </div>
                    `);
                }
            }

            // --- PRECIPITACIÓN PLUVIAL EN RIEGO ---
            function cargarGraficaPrecipitacionPluvial() {
                let zonaId = window.filtros.zona_manejo_id;
                let periodo = window.filtros.periodo;
                let startDate = window.filtros.startDate;
                let endDate = window.filtros.endDate;
                let tipoCultivoId = window.filtros.tipo_cultivo_id;
                let etapaFenologicaId = window.filtros.etapa_fenologica_id;

                if (zonaId) {
                    mostrarLoader('Cargando gráficas de precipitación pluvial...');
                    $('#component_grafica_precipitacion_pluvial').html(`
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando gráficas de precipitación pluvial...</p>
                        </div>
                    `);

                    let url = "{{ route('component_grafica_precipitacion_pluvial', ['zonaManejoId' => ':zonaId']) }}"
                        .replace(':zonaId', zonaId) +
                        '?periodo=' + periodo +
                        '&startDate=' + startDate +
                        '&endDate=' + endDate +
                        '&tipo_cultivo_id=' + tipoCultivoId +
                        '&etapa_fenologica_id=' + etapaFenologicaId;

                    $.get(url, function(html) {
                        $('#component_grafica_precipitacion_pluvial').html(html);
                        setTimeout(function() {
                            if (typeof window.cargarGraficasPrecipitacionPluvial === 'function') {
                                window.cargarGraficasPrecipitacionPluvial();
                            }
                            ocultarLoader();
                        }, 200);
                    }).fail(function() {
                        $('#component_grafica_precipitacion_pluvial').html(`
                            <div class="alert alert-danger">
                                Error al cargar las gráficas de precipitación pluvial.
                            </div>
                        `);
                        ocultarLoader();
                    });
                } else {
                    $('#component_grafica_precipitacion_pluvial').html(`
                        <div class="alert alert-info">
                            Selecciona una zona de manejo para ver las gráficas de precipitación pluvial.
                        </div>
                    `);
                }
            }

            // Cargar automáticamente todas las gráficas de riego cuando se muestre la sección de riego
            $(document).on('shown.bs.tab', 'a[data-toggle="tab"][href="#justified-right-icon-tab4"]', function() {
                cargarGraficaHumedadRelativa();
                cargarGraficaHumedadSuelo();
                cargarGraficaPrecipitacionPluvial();
            });
        </script>
        <script>
            // Función para cargar el componente de gráfica de CO2
            function cargarGraficaPH() {
                let zonaId = window.filtros.zona_manejo_id;
                let periodo = window.filtros.periodo;
                let startDate = window.filtros.startDate;
                let endDate = window.filtros.endDate;
                let tipoCultivoId = window.filtros.tipo_cultivo_id;
                let etapaFenologicaId = window.filtros.etapa_fenologica_id;

                if (zonaId) {
                    // Mostrar loader
                    mostrarLoader('Cargando gráfica de pH...');
                    $('#component_grafica_ph').html(`
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando gráfica de pH...</p>
                        </div>
                    `);
                    let url = "{{ route('component_grafica_ph', ['zonaManejoId' => ':zonaId']) }}"
                        .replace(':zonaId', zonaId) +
                        '?periodo=' + periodo +
                        '&startDate=' + startDate +
                        '&endDate=' + endDate +
                        '&tipo_cultivo_id=' + tipoCultivoId +
                        '&etapa_fenologica_id=' + etapaFenologicaId;

                    $.get(url, function(html) {
                        $('#component_grafica_ph').html(html);
                        setTimeout(function() {
                            if (typeof window.cargarGraficasPH === 'function') {
                                window.cargarGraficasPH();
                            }
                            // Ocultar loader después de cargar
                            ocultarLoader();
                        }, 200);
                    }).fail(function(xhr, status, error) {
                        $('#component_grafica_ph').html(`
                            <div class="alert alert-danger">
                                Error al cargar la gráfica de pH.<br>
                                Status: ${status}<br>
                                Error: ${error}
                            </div>
                        `);
                        // Ocultar loader en caso de error
                        ocultarLoader();
                    });
                } else {
                    $('#component_grafica_ph').html(`
                        <div class="alert alert-info">
                            Selecciona una zona de manejo para ver la gráfica de pH.
                        </div>
                    `);
                }
            }

            // Nitrógeno

            function cargarGraficaNitrogeno() {
                let zonaId = window.filtros.zona_manejo_id;
                let periodo = window.filtros.periodo;
                let startDate = window.filtros.startDate;
                let endDate = window.filtros.endDate;
                let tipoCultivoId = window.filtros.tipo_cultivo_id;
                let etapaFenologicaId = window.filtros.etapa_fenologica_id;

                if (zonaId) {
                    // Mostrar loader
                    mostrarLoader('Cargando gráfica de Nitrógeno...');
                    $('#component_grafica_nitrogeno').html(`
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando gráfica de Nitrógeno...</p>
                        </div>
                    `);
                    let url = "{{ route('component_grafica_nitrogeno', ['zonaManejoId' => ':zonaId']) }}"
                        .replace(':zonaId', zonaId) +
                        '?periodo=' + periodo +
                        '&startDate=' + startDate +
                        '&endDate=' + endDate +
                        '&tipo_cultivo_id=' + tipoCultivoId +
                        '&etapa_fenologica_id=' + etapaFenologicaId;

                    $.get(url, function(html) {
                        $('#component_grafica_nitrogeno').html(html);
                        setTimeout(function() {
                            if (typeof window.cargarGraficasNitrogeno === 'function') {
                                window.cargarGraficasNitrogeno();
                            }
                            // Ocultar loader después de cargar
                            ocultarLoader();
                        }, 200);
                    }).fail(function(xhr, status, error) {
                        $('#component_grafica_nitrogeno').html(`
                            <div class="alert alert-danger">
                                Error al cargar la gráfica de Nitrógeno.<br>
                                Status: ${status}<br>
                                Error: ${error}
                            </div>
                        `);
                        // Ocultar loader en caso de error
                        ocultarLoader();
                    });
                } else {
                    $('#component_grafica_nitrogeno').html(`
                        <div class="alert alert-info">
                            Selecciona una zona de manejo para ver la gráfica de Nitrógeno.
                        </div>
                    `);
                }
            }

            // Fósforo
            function cargarGraficaFosforo() {
                let zonaId = window.filtros.zona_manejo_id;
                let periodo = window.filtros.periodo;
                let startDate = window.filtros.startDate;
                let endDate = window.filtros.endDate;
                let tipoCultivoId = window.filtros.tipo_cultivo_id;
                let etapaFenologicaId = window.filtros.etapa_fenologica_id;

                if (zonaId) {
                    // Mostrar loader
                    mostrarLoader('Cargando gráfica de Fósforo...');
                    $('#component_grafica_fosforo').html(`
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando gráfica de Fósforo...</p>
                        </div>
                    `);
                    let url = "{{ route('component_grafica_fosforo', ['zonaManejoId' => ':zonaId']) }}"
                        .replace(':zonaId', zonaId) +
                        '?periodo=' + periodo +
                        '&startDate=' + startDate +
                        '&endDate=' + endDate +
                        '&tipo_cultivo_id=' + tipoCultivoId +
                        '&etapa_fenologica_id=' + etapaFenologicaId;

                    $.get(url, function(html) {
                        $('#component_grafica_fosforo').html(html);
                        setTimeout(function() {
                            if (typeof window.cargarGraficasFosforo === 'function') {
                                window.cargarGraficasFosforo();
                            }
                            // Ocultar loader después de cargar
                            ocultarLoader();
                        }, 200);
                    }).fail(function(xhr, status, error) {
                        $('#component_grafica_fosforo').html(`
                            <div class="alert alert-danger">
                                Error al cargar la gráfica de Fósforo.<br>
                                Status: ${status}<br>
                                Error: ${error}
                            </div>
                        `);
                        // Ocultar loader en caso de error
                        ocultarLoader();
                    });
                } else {
                    $('#component_grafica_fosforo').html(`
                        <div class="alert alert-info">
                            Selecciona una zona de manejo para ver la gráfica de Fósforo.
                        </div>
                    `);
                }
            }

            // Potasio
            function cargarGraficaPotasio() {
                let zonaId = window.filtros.zona_manejo_id;
                let periodo = window.filtros.periodo;
                let startDate = window.filtros.startDate;
                let endDate = window.filtros.endDate;
                let tipoCultivoId = window.filtros.tipo_cultivo_id;
                let etapaFenologicaId = window.filtros.etapa_fenologica_id;

                if (zonaId) {
                    // Mostrar loader
                    mostrarLoader('Cargando gráfica de Potasio...');
                    $('#component_grafica_potasio').html(`
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando gráfica de Potasio...</p>
                        </div>
                    `);
                    let url = "{{ route('component_grafica_potasio', ['zonaManejoId' => ':zonaId']) }}"
                        .replace(':zonaId', zonaId) +
                        '?periodo=' + periodo +
                        '&startDate=' + startDate +
                        '&endDate=' + endDate +
                        '&tipo_cultivo_id=' + tipoCultivoId +
                        '&etapa_fenologica_id=' + etapaFenologicaId;

                    $.get(url, function(html) {
                        $('#component_grafica_potasio').html(html);
                        setTimeout(function() {
                            if (typeof window.cargarGraficasPotasio === 'function') {
                                window.cargarGraficasPotasio();
                            }
                            // Ocultar loader después de cargar
                            ocultarLoader();
                        }, 200);
                    }).fail(function(xhr, status, error) {
                        $('#component_grafica_potasio').html(`
                            <div class="alert alert-danger">
                                Error al cargar la gráfica de Potasio.<br>
                                Status: ${status}<br>
                                Error: ${error}
                            </div>
                        `);
                        // Ocultar loader en caso de error
                        ocultarLoader();
                    });
                } else {
                    $('#component_grafica_potasio').html(`
                        <div class="alert alert-info">
                            Selecciona una zona de manejo para ver la gráfica de Potasio.
                        </div>
                    `);
                }
            }

            // Conductividad Eléctrica
            function cargarGraficaConductividadElectrica() {
                let zonaId = window.filtros.zona_manejo_id;
                let periodo = window.filtros.periodo;
                let startDate = window.filtros.startDate;
                let endDate = window.filtros.endDate;
                let tipoCultivoId = window.filtros.tipo_cultivo_id;
                let etapaFenologicaId = window.filtros.etapa_fenologica_id;

                if (zonaId) {
                    // Mostrar loader
                    mostrarLoader('Cargando gráfica de Conductividad Eléctrica...');
                    $('#component_grafica_conductividad_electrica').html(`
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                            <p class="mt-2">Cargando gráfica de Conductividad Eléctrica...</p>
                        </div>
                    `);
                    let url = "{{ route('component_grafica_conductividad_electrica', ['zonaManejoId' => ':zonaId']) }}"
                        .replace(':zonaId', zonaId) +
                        '?periodo=' + periodo +
                        '&startDate=' + startDate +
                        '&endDate=' + endDate +
                        '&tipo_cultivo_id=' + tipoCultivoId +
                        '&etapa_fenologica_id=' + etapaFenologicaId;

                    $.get(url, function(html) {
                        $('#component_grafica_conductividad_electrica').html(html);
                        setTimeout(function() {
                            if (typeof window.cargarGraficasConductividadElectrica === 'function') {
                                window.cargarGraficasConductividadElectrica();
                            }
                            // Ocultar loader después de cargar
                            ocultarLoader();
                        }, 200);
                    }).fail(function(xhr, status, error) {
                        $('#component_grafica_conductividad_electrica').html(`
                            <div class="alert alert-danger">
                                Error al cargar la gráfica de Conductividad Eléctrica.<br>
                                Status: ${status}<br>
                                Error: ${error}
                            </div>
                        `);
                        // Ocultar loader en caso de error
                        ocultarLoader();
                    });
                } else {
                    $('#component_grafica_conductividad_electrica').html(`
                        <div class="alert alert-info">
                            Selecciona una zona de manejo para ver la gráfica de Conductividad Eléctrica.
                        </div>
                    `);
                }
            }

            // Cargar gráfica de pH cuando se haga clic en el tab de Nutrición
            $(document).on('shown.bs.tab', 'a[data-toggle="tab"][href="#justified-right-icon-tab3"]', function() {
                cargarGraficaPH();
            });

            // Cargar gráfica de Humedad Relativa cuando se haga clic en el tab de Riego
            $(document).on('shown.bs.tab', 'a[data-toggle="tab"][href="#justified-right-icon-tab4"]', function() {
                cargarGraficaHumedadRelativa();
            });

            // Cargar gráfica de pH
            $(document).on('shown.bs.tab', 'a[data-toggle="tab"][href="#nutricion-tab1"]', function() {
                cargarGraficaPH();
            });

            // Cargar gráfica de Nitrógeno
            $(document).on('shown.bs.tab', 'a[data-toggle="tab"][href="#nutricion-tab2"]', function() {
                cargarGraficaNitrogeno();
            });

            // Cargar gráfica de Fósforo
            $(document).on('shown.bs.tab', 'a[data-toggle="tab"][href="#nutricion-tab3"]', function() {
                cargarGraficaFosforo();
            });

            // Cargar gráfica de Potasio
            $(document).on('shown.bs.tab', 'a[data-toggle="tab"][href="#nutricion-tab4"]', function() {
                cargarGraficaPotasio();
            });

            // Cargar gráfica de Conductividad Eléctrica
            $(document).on('shown.bs.tab', 'a[data-toggle="tab"][href="#nutricion-tab5"]', function() {
                cargarGraficaConductividadElectrica();
            });

            // Cargar gráfica de Humedad Relativa
            $(document).on('shown.bs.tab', 'a[data-toggle="tab"][href="#riego-tab1"]', function() {
                cargarGraficaHumedadRelativa();
            });

            // Cargar gráfica de Humedad del Suelo
            $(document).on('shown.bs.tab', 'a[data-toggle="tab"][href="#riego-tab2"]', function() {
                cargarGraficaHumedadSuelo();
            });

            // Cargar gráfica de Precipitación Pluvial
            $(document).on('shown.bs.tab', 'a[data-toggle="tab"][href="#riego-tab3"]', function() {
                cargarGraficaPrecipitacionPluvial();
            });

            // Funciones de exportación
            function exportarEstacionDato() {
                let zonaId = window.filtros.zona_manejo_id;
                let periodo = window.filtros.periodo;
                let startDate = window.filtros.startDate;
                let endDate = window.filtros.endDate;

                if (!zonaId || zonaId === "" || zonaId === "0") {
                    mostrarNotificacion('Debes seleccionar una zona de manejo para exportar los datos', 'error');
                    return;
                }

                // Mostrar loader
                mostrarLoader('Preparando exportación del periodo seleccionado...');

                // Construir URL con parámetros
                let url = "{{ route('exportar-estacion-dato') }}";
                let params = new URLSearchParams();
                params.set('zona_manejo_id', zonaId);
                params.set('periodo', periodo);

                if (startDate && startDate !== "" && startDate !== "null") {
                    params.set('start_date', startDate);
                }
                if (endDate && endDate !== "" && endDate !== "null") {
                    params.set('end_date', endDate);
                }

                // Crear un enlace temporal para descargar el archivo
                let downloadLink = document.createElement('a');
                downloadLink.href = url + '?' + params.toString();
                downloadLink.download = 'mediciones_periodo.xlsx';
                downloadLink.style.display = 'none';
                document.body.appendChild(downloadLink);

                // Simular clic para iniciar la descarga
                downloadLink.click();

                // Limpiar el enlace temporal
                document.body.removeChild(downloadLink);

                // Ocultar loader después de un breve delay
                setTimeout(function() {
                    ocultarLoader();
                    mostrarNotificacion('Exportación iniciada. El archivo se descargará automáticamente.', 'success');
                }, 9000);
            }

            function exportarEstacionDatoCompleto() {
                let zonaId = window.filtros.zona_manejo_id;

                if (!zonaId || zonaId === "" || zonaId === "0") {
                    mostrarNotificacion('Debes seleccionar una zona de manejo para exportar todos los datos', 'error');
                    return;
                }

                // Ocultar botones de formato y mostrar progreso
                $('#format-buttons').hide();
                actualizarProgreso(10, 'Iniciando exportación CSV...');

                // Construir URL con parámetros (solo zona_manejo_id)
                // Usar CSV por defecto para mejor rendimiento con grandes volúmenes
                let url = "{{ route('exportar-estacion-dato-all-csv') }}";
                let params = new URLSearchParams();
                params.set('zona_manejo_id', zonaId);

                // Iniciar monitoreo de progreso
                let progresoInterval;
                let intentos = 0;
                const maxIntentos = 300; // Máximo 25 minutos (300 * 5 segundos)
                let exportStarted = false;

                function verificarProgreso() {
                    const progressParams = new URLSearchParams();
                    progressParams.set('zona_manejo_id', zonaId);

                    fetch("{{ route('check-export-progress') }}?" + progressParams.toString())
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                throw new Error(data.error);
                            }

                            const progreso = data.progress || 0;
                            const totalRecords = data.total_records || 0;
                            const processedRecords = data.processed_records || 0;
                            const estimatedTime = data.estimated_time || 'Calculando...';
                            const status = data.status || 'processing';

                            // Solo actualizar progreso si la exportación ya comenzó
                            if (exportStarted || progreso > 10) {
                                exportStarted = true;
                                actualizarProgreso(progreso,
                                    `Procesando ${processedRecords.toLocaleString()} de ${totalRecords.toLocaleString()} registros (${estimatedTime})`
                                );
                            }

                            // No marcar como completado hasta que realmente termine
                            if (status === 'finalizing' && progreso >= 85) {
                                actualizarProgreso(85, 'Finalizando exportación...');
                            }
                        })
                        .catch(error => {
                            console.error('Error al verificar progreso:', error);
                            intentos++;

                            if (intentos >= maxIntentos) {
                                clearInterval(progresoInterval);
                                actualizarProgreso(0, 'Error: Tiempo de espera agotado');
                            }
                        });
                }

                // Verificar progreso cada 5 segundos
                progresoInterval = setInterval(verificarProgreso, 5000);
                verificarProgreso(); // Verificación inicial

                // Realizar la petición de exportación
                fetch(url + '?' + params.toString(), {
                        method: 'GET',
                        headers: {
                            'Accept': 'text/csv, application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        clearInterval(progresoInterval);

                        if (!response.ok) {
                            // Si es un error JSON, intentar parsearlo
                            if (response.headers.get('content-type')?.includes('application/json')) {
                                return response.json().then(errorData => {
                                    throw new Error(errorData.error || 'Error en la exportación');
                                });
                            }
                            throw new Error(`Error HTTP: ${response.status}`);
                        }

                        // Verificar si es un archivo CSV
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('text/csv')) {
                            // Es un archivo CSV, proceder con la descarga
                            actualizarProgreso(90, 'Preparando descarga...');
                            return response.blob();
                        } else {
                            // Intentar parsear como JSON para ver si hay mensaje de error
                            return response.json().then(data => {
                                if (data.error) {
                                    throw new Error(data.error);
                                }
                                throw new Error('Respuesta inesperada del servidor');
                            });
                        }
                    })
                    .then(blob => {
                        // Crear URL del blob y descargar
                        actualizarProgreso(95, 'Descargando archivo...');

                        const downloadUrl = window.URL.createObjectURL(blob);
                        const downloadLink = document.createElement('a');
                        downloadLink.href = downloadUrl;
                        downloadLink.download =
                            `todas_mediciones_zona_${zonaId}_${new Date().toISOString().split('T')[0]}.csv`;
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                        window.URL.revokeObjectURL(downloadUrl);

                        // Completar progreso
                        actualizarProgreso(100, '¡Archivo CSV descargado exitosamente!');

                        setTimeout(() => {
                            ocultarModalProgreso();
                            mostrarNotificacion('Exportación CSV completada exitosamente', 'success');
                        }, 2000);
                    })
                    .catch(error => {
                        clearInterval(progresoInterval);
                        console.error('Error en exportación:', error);

                        actualizarProgreso(0, 'Error: ' + error.message);

                        setTimeout(() => {
                            ocultarModalProgreso();
                            mostrarNotificacion('Error en la exportación: ' + error.message, 'error');
                        }, 3000);
                    });
            }

            function exportarEstacionDatoCompletoExcel() {
                let zonaId = window.filtros.zona_manejo_id;

                if (!zonaId || zonaId === "" || zonaId === "0") {
                    mostrarNotificacion('Debes seleccionar una zona de manejo para exportar todos los datos', 'error');
                    return;
                }

                // Ocultar botones de formato y mostrar progreso
                $('#format-buttons').hide();
                actualizarProgreso(10, 'Iniciando exportación Excel...');

                // Construir URL con parámetros (solo zona_manejo_id)
                let url = "{{ route('exportar-estacion-dato-all-optimized') }}";
                let params = new URLSearchParams();
                params.set('zona_manejo_id', zonaId);

                // Iniciar monitoreo de progreso
                let progresoInterval;
                let intentos = 0;
                const maxIntentos = 300; // Máximo 25 minutos (300 * 5 segundos)
                let exportStarted = false;

                function verificarProgreso() {
                    const progressParams = new URLSearchParams();
                    progressParams.set('zona_manejo_id', zonaId);

                    fetch("{{ route('check-export-progress') }}?" + progressParams.toString())
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                throw new Error(data.error);
                            }

                            const progreso = data.progress || 0;
                            const totalRecords = data.total_records || 0;
                            const processedRecords = data.processed_records || 0;
                            const estimatedTime = data.estimated_time || 'Calculando...';
                            const status = data.status || 'processing';

                            // Solo actualizar progreso si la exportación ya comenzó
                            if (exportStarted || progreso > 10) {
                                exportStarted = true;
                                actualizarProgreso(progreso,
                                    `Procesando ${processedRecords.toLocaleString()} de ${totalRecords.toLocaleString()} registros (${estimatedTime})`
                                );
                            }

                            // No marcar como completado hasta que realmente termine
                            if (status === 'finalizing' && progreso >= 85) {
                                actualizarProgreso(85, 'Finalizando exportación...');
                            }
                        })
                        .catch(error => {
                            console.error('Error al verificar progreso:', error);
                            intentos++;

                            if (intentos >= maxIntentos) {
                                clearInterval(progresoInterval);
                                actualizarProgreso(0, 'Error: Tiempo de espera agotado');
                            }
                        });
                }

                // Verificar progreso cada 5 segundos
                progresoInterval = setInterval(verificarProgreso, 5000);
                verificarProgreso(); // Verificación inicial

                // Realizar la petición de exportación
                fetch(url + '?' + params.toString(), {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        clearInterval(progresoInterval);

                        if (!response.ok) {
                            // Si es un error JSON, intentar parsearlo
                            if (response.headers.get('content-type')?.includes('application/json')) {
                                return response.json().then(errorData => {
                                    throw new Error(errorData.error || 'Error en la exportación');
                                });
                            }
                            throw new Error(`Error HTTP: ${response.status}`);
                        }

                        // Verificar si es un archivo Excel
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('spreadsheet')) {
                            // Es un archivo Excel, proceder con la descarga
                            actualizarProgreso(90, 'Preparando descarga...');
                            return response.blob();
                        } else {
                            // Intentar parsear como JSON para ver si hay mensaje de error
                            return response.json().then(data => {
                                if (data.error) {
                                    throw new Error(data.error);
                                }
                                throw new Error('Respuesta inesperada del servidor');
                            });
                        }
                    })
                    .then(blob => {
                        // Crear URL del blob y descargar
                        actualizarProgreso(95, 'Descargando archivo...');

                        const downloadUrl = window.URL.createObjectURL(blob);
                        const downloadLink = document.createElement('a');
                        downloadLink.href = downloadUrl;
                        downloadLink.download =
                            `todas_mediciones_zona_${zonaId}_${new Date().toISOString().split('T')[0]}.xlsx`;
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                        window.URL.revokeObjectURL(downloadUrl);

                        // Completar progreso
                        actualizarProgreso(100, '¡Archivo Excel descargado exitosamente!');

                        setTimeout(() => {
                            ocultarModalProgreso();
                            mostrarNotificacion('Exportación Excel completada exitosamente', 'success');
                        }, 2000);
                    })
                    .catch(error => {
                        clearInterval(progresoInterval);
                        console.error('Error en exportación:', error);

                        actualizarProgreso(0, 'Error: ' + error.message);

                        setTimeout(() => {
                            ocultarModalProgreso();
                            mostrarNotificacion('Error en la exportación: ' + error.message, 'error');
                        }, 3000);
                    });
            }

            // Función para mostrar modal de progreso
            function mostrarModalProgreso() {
                // Crear modal si no existe
                if (!$('#modal-progreso').length) {
                    const modalHtml = `
                        <div id="modal-progreso" class="modal fade" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
                            <div class="modal-dialog modal-sm" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Exportando datos...</h5>
                                    </div>
                                    <div class="modal-body text-center">
                                        <div class="mb-3">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="sr-only">Procesando...</span>
                                            </div>
                                        </div>
                                        <div class="progress mb-3">
                                            <div id="barra-progreso" class="progress-bar progress-bar-striped progress-bar-animated" 
                                                 role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                                0%
                                            </div>
                                        </div>
                                        <div id="texto-progreso" class="text-muted">
                                            Preparando exportación...
                                        </div>
                                        
                                        <!-- Botones de formato -->
                                        <div class="mt-3" id="format-buttons" style="display: none;">
                                            <small class="text-muted mb-2 d-block">Formato de exportación:</small>
                                            <div class="btn-group-vertical btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-primary btn-sm mb-1" onclick="exportarEstacionDatoCompleto()">
                                                    <i class="fas fa-file-csv"></i> CSV (Recomendado)
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="exportarEstacionDatoCompletoExcel()">
                                                    <i class="fas fa-file-excel"></i> Excel
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    $('body').append(modalHtml);
                }

                // Mostrar modal y botones de formato
                $('#modal-progreso').modal('show');
                $('#format-buttons').show();
            }

            // Función para actualizar progreso
            function actualizarProgreso(porcentaje, texto = null) {
                const barra = $('#barra-progreso');
                const textoElement = $('#texto-progreso');

                // Actualizar barra de progreso
                barra.css('width', porcentaje + '%');
                barra.attr('aria-valuenow', porcentaje);
                barra.text(Math.round(porcentaje) + '%');

                // Actualizar texto
                if (texto) {
                    textoElement.text(texto);
                } else {
                    if (porcentaje < 20) {
                        textoElement.text('Validando datos...');
                    } else if (porcentaje < 40) {
                        textoElement.text('Procesando registros...');
                    } else if (porcentaje < 60) {
                        textoElement.text('Generando archivo...');
                    } else if (porcentaje < 80) {
                        textoElement.text('Finalizando exportación...');
                    } else if (porcentaje < 90) {
                        textoElement.text('Preparando descarga...');
                    } else if (porcentaje < 100) {
                        textoElement.text('Descargando archivo...');
                    } else {
                        textoElement.text('¡Exportación completada!');
                    }
                }

                // Cambiar color según el estado
                if (porcentaje === 100) {
                    barra.removeClass('progress-bar-striped progress-bar-animated bg-warning').addClass('bg-success');
                } else if (porcentaje >= 85) {
                    barra.removeClass('progress-bar-striped progress-bar-animated bg-primary').addClass('bg-warning');
                } else if (texto && texto.includes('Error')) {
                    barra.removeClass('progress-bar-striped progress-bar-animated bg-primary bg-warning').addClass('bg-danger');
                } else {
                    barra.removeClass('bg-success bg-warning bg-danger').addClass(
                        'progress-bar-striped progress-bar-animated bg-primary');
                }

                // Mostrar indicador de sincronización
                if (porcentaje > 0 && porcentaje < 100) {
                    textoElement.append(' <small class="text-muted">(Sincronizado)</small>');
                }
            }

            // Función para ocultar modal de progreso
            function ocultarModalProgreso() {
                $('#modal-progreso').modal('hide');
            }
        </script>
        <script>
            // Botón para restablecer filtros
            $('#reset-filtros-btn').on('click', function() {
                window.location.href = '{{ route('grupos.zonas-manejo') }}';
            });

            // Variables para almacenar las variables disponibles
            let variablesDisponibles = [];

            // Cargar variables de medición al cargar la página
            function cargarVariablesMedicion() {
                $.ajax({
                    url: '/api/variables-disponibles',
                    method: 'GET',
                    success: function(response) {
                        if (response.variables_disponibles) {
                            variablesDisponibles = response.variables_disponibles;
                            actualizarSelectVariables();
                        }
                    },
                    error: function(xhr) {
                        console.error('Error al cargar variables:', xhr);
                        $('#variables_medicion').html('<option value="">Error al cargar variables</option>');
                    }
                });
            }

            // Actualizar el select de variables con solo las compatibles
            function actualizarSelectVariables() {
                let html = '';

                // Mapeo de variables compatibles con estacion_dato
                const variablesCompatibles = {
                    'temperatura': 'Temperatura (°C)',
                    'humedad_relativa': 'Humedad Relativa (%)',
                    'radiacion_solar': 'Radiación Solar (W/m²)',
                    'precipitacion_acumulada': 'Precipitación Acumulada (mm)',
                    'velocidad_viento': 'Velocidad del Viento (m/s)',
                    'direccion_viento': 'Dirección del Viento (°)',
                    'co2': 'CO2 (ppm)',
                    'ph': 'pH',
                    'phos': 'Fósforo (ppm)',
                    'nit': 'Nitrógeno (ppm)',
                    'pot': 'Potasio (ppm)',
                    'temperatura_suelo': 'Temperatura del Suelo (°C)',
                    'conductividad_electrica': 'Conductividad Eléctrica (Ds/m)',
                    'potencial_de_hidrogeno': 'Potencial de Hidrógeno',
                    'viento': 'Viento (m/s)',
                    'humedad_15': 'Humedad 15 (%)',
                    'temperatura_lvl1': 'Temperatura Nivel 1 (°C)'
                };

                Object.entries(variablesCompatibles).forEach(([slug, nombre]) => {
                    html += `<option value="${slug}">${nombre}</option>`;
                });

                $('#variables_medicion').html(html);
                // Reinicializar el multiselect
                $('#variables_medicion').multiselect('rebuild');
            }

            // Limpiar selección
            $('#limpiar-seleccion').on('click', function() {
                $('#variables_medicion').multiselect('deselectAll', false);
                $('#agrupacion').multiselect('deselectAll', false);
                $('#resultado-interaccion').empty();
                $('#graficas-variables').hide();
            });

            // Manejar el botón de mostrar gráficas
            $('#mostrar-graficas').on('click', function() {
                const variables = $('#variables_medicion').val();
                const agrupaciones = $('#agrupacion').val();

                if (!variables || variables.length === 0) {
                    alert('Por favor selecciona al menos una variable');
                    return;
                }

                if (!agrupaciones || agrupaciones.length === 0) {
                    alert('Por favor selecciona al menos una agrupación');
                    return;
                }

                mostrarGraficasPorVariable(variables, agrupaciones);
            });

            // Función para mostrar gráficas por variable
            function mostrarGraficasPorVariable(variables, agrupaciones) {
                console.log('=== INICIO mostrarGraficasPorVariable ===');
                console.log('Variables:', variables);
                console.log('Agrupaciones:', agrupaciones);

                // Verificar que ECharts esté disponible
                if (typeof echarts === 'undefined') {
                    console.error('ECharts no está disponible');
                    alert('Error: ECharts no está disponible. Por favor, recarga la página.');
                    return;
                }

                const zonaManejoId = $('#zonas').val();
                const periodo = $('#selectPeriodo').val();
                const startDate = $('input[name="startDate"]').val();
                const endDate = $('input[name="endDate"]').val();

                console.log('Parámetros obtenidos:', {
                    zonaManejoId,
                    periodo,
                    startDate,
                    endDate
                });

                if (!zonaManejoId) {
                    alert('Por favor selecciona una zona de manejo');
                    return;
                }

                // Mostrar contenedor de gráficas
                $('#graficas-variables').show();
                $('#contenedor-graficas').empty();
                console.log('Contenedor de gráficas preparado');

                // Crear UNA SOLA gráfica para todas las variables
                const timestamp = Date.now();
                const graficaId = `interaccion_grafica_unificada_${timestamp}`;
                const cardId = `interaccion_card_unificada_${timestamp}`;

                console.log('ID de gráfica unificada:', graficaId);

                // Crear contenedor para la gráfica unificada
                const html = `
                    <div class="col-md-12 mb-4" id="${cardId}">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Gráfica de Variables Múltiples</h6>
                            </div>
                            <div class="card-body">
                                <div id="${graficaId}" style="height: 400px;">
                                    <div class="text-center p-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="sr-only">Cargando...</span>
                                        </div>
                                        <p class="mt-2">Cargando gráfica...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                $('#contenedor-graficas').append(html);
                console.log('Contenedor de gráfica unificada creado');

                // Crear la gráfica unificada
                const chart = GraficaVariablesMultiples.createChart(graficaId);

                if (!chart) {
                    console.error('No se pudo crear la gráfica unificada:', graficaId);
                    return;
                }

                console.log('Gráfica unificada creada exitosamente:', graficaId);

                // Preparar parámetros para TODAS las variables
                const params = {
                    variables: variables, // Todas las variables seleccionadas
                    agrupaciones: agrupaciones,
                    estacion_id: parseInt(zonaManejoId),
                    periodo: parseInt(periodo)
                };

                // Agregar fechas si están definidas
                if (startDate && endDate) {
                    params.startDate = startDate;
                    params.endDate = endDate;
                }

                console.log('Parámetros para cargarDatos (todas las variables):', params);

                // Cargar datos para todas las variables en una sola gráfica
                GraficaVariablesMultiples.cargarDatos(graficaId, params);

                console.log('=== FIN mostrarGraficasPorVariable ===');
            }

            // Función para obtener el nombre legible de la variable
            function getNombreVariable(slug) {
                const nombres = {
                    'temperatura': 'Temperatura (°C)',
                    'humedad_relativa': 'Humedad Relativa (%)',
                    'radiacion_solar': 'Radiación Solar (W/m²)',
                    'precipitacion_acumulada': 'Precipitación Acumulada (mm)',
                    'velocidad_viento': 'Velocidad del Viento (m/s)',
                    'direccion_viento': 'Dirección del Viento (°)',
                    'co2': 'CO2 (ppm)',
                    'ph': 'pH',
                    'phos': 'Fósforo (ppm)',
                    'nit': 'Nitrógeno (ppm)',
                    'pot': 'Potasio (ppm)',
                    'temperatura_suelo': 'Temperatura del Suelo (°C)',
                    'conductividad_electrica': 'Conductividad Eléctrica (Ds/m)',
                    'potencial_de_hidrogeno': 'Potencial de Hidrógeno',
                    'viento': 'Viento (m/s)',
                    'humedad_15': 'Humedad 15 (%)',
                    'temperatura_lvl1': 'Temperatura Nivel 1 (°C)'
                };

                return nombres[slug] || slug;
            }

            function mostrarResultadosInteraccion(data) {
                const esUnaVariable = data.variables.length === 1;

                let html = `
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">${esUnaVariable ? 'Análisis de Variable' : 'Análisis de Interacción de Factores'}</h6>
                            <small class="text-muted">Período: ${data.periodo.inicio} - ${data.periodo.fin} (${data.periodo.tipo})</small>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Variables analizadas:</h6>
                                    <ul>
                                        ${data.variables.map(v => `<li>${v.nombre} (${v.unidad})</li>`).join('')}
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Agrupaciones:</h6>
                                    <ul>
                                        ${data.agrupaciones.map(a => `<li>${a.split('|')[1]}</li>`).join('')}
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6>Datos obtenidos (promedio del período):</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Variable</th>
                                                    ${data.agrupaciones.map(a => `<th>${a.split('|')[1]}</th>`).join('')}
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${Object.entries(data.datos).map(([variable, valores]) => `<tr>`).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                `;

                // Solo mostrar correlaciones si hay más de una variable
                if (data.correlaciones.length > 0) {
                    html += `
                            <div class="row mt-3">
                                <div class="col-12">
                                    <h6>Correlaciones entre variables:</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Variable 1</th>
                                                    <th>Variable 2</th>
                                                    <th>Coeficiente</th>
                                                    <th>Interpretación</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${data.correlaciones.map(corr => `                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                    `;
                } else if (data.variables.length > 1) {
                    html += `
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="icon-info22"></i> No se pudieron calcular correlaciones entre las variables seleccionadas.
                                    </div>
                                </div>
                            </div>
                    `;
                }

                // Mostrar interpretación de correlaciones solo si hay correlaciones
                if (data.correlaciones.length > 0) {
                    html += `
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <h6>Interpretación de correlaciones:</h6>
                                        <ul class="mb-0">
                                            <li><strong>Muy alta (≥0.8):</strong> Las variables están muy relacionadas</li>
                                            <li><strong>Alta (≥0.6):</strong> Existe una relación importante entre las variables</li>
                                            <li><strong>Moderada (≥0.4):</strong> Hay una relación moderada</li>
                                            <li><strong>Baja (≥0.2):</strong> La relación es débil</li>
                                            <li><strong>Muy baja (<0.2):</strong> Prácticamente no hay relación</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                    `;
                }

                html += `
                        </div>
                    </div>
                `;

                $('#resultado-interaccion').html(html);
            }

            function getCorrelacionColor(correlacion) {
                const abs = Math.abs(correlacion);
                if (abs >= 0.8) return 'danger';
                if (abs >= 0.6) return 'warning';
                if (abs >= 0.4) return 'info';
                if (abs >= 0.2) return 'secondary';
                return 'light';
            }

            // Inicializar al cargar la página
            $(document).ready(function() {
                cargarVariablesMedicion();

                // Pre-cargar variables comunes para mejorar rendimiento
                setTimeout(precargarVariablesComunes, 2000);
            });

            // Pre-cargar datos para variables comunes (como areas.js)
            function precargarVariablesComunes() {
                const zonaManejoId = $('#zonas').val();
                if (!zonaManejoId) return;

                console.log('Pre-cargando variables comunes...');

                // Variables más utilizadas
                const variablesComunes = ['temperatura', 'humedad_relativa'];
                const agrupacionesComunes = ['max|Máximo', 'min|Mínimo', 'avg|Promedio'];

                variablesComunes.forEach((variable, index) => {
                    const graficaId = `preload_${variable}_${index}`;

                    // Crear contenedor oculto
                    const html = `
                <div style="display: none;">
                    <div id="${graficaId}" style="height: 300px;"></div>
                </div>
            `;
                    $('body').append(html);

                    // Crear gráfica y cargar datos
                    setTimeout(() => {
                        if (typeof GraficaVariablesMultiples !== 'undefined') {
                            const chart = GraficaVariablesMultiples.createChart(graficaId);
                            if (chart) {
                                const params = {
                                    variables: [variable],
                                    agrupaciones: agrupacionesComunes,
                                    estacion_id: parseInt(zonaManejoId),
                                    periodo: 3 // Período por defecto
                                };
                                GraficaVariablesMultiples.cargarDatos(graficaId, params);
                                console.log(`Pre-carga completada para: ${variable}`);
                            }
                        }
                    }, 1000 + (index * 500)); // Espaciado para no sobrecargar
                });
            }

    function initTab8SueloCorrectivos() {
        const $selectAnio = $('#selectCorrectivosAnio');
        const $tbody = $('#tablaCorrectivosBody');

        // Si no existe en el DOM, salimos
        if (!$selectAnio.length || !$tbody.length) return;

        // Evitar duplicar listeners si se llama múltiples veces
        if ($selectAnio.data('listenerAdded')) return;
        $selectAnio.data('listenerAdded', true);

        // Para abortar requests anteriores si cambias rápido de año
        let abortController = null;

        function getZonaManejoId() {
            // 1) desde tus filtros globales
            let zonaId = (window.filtros && window.filtros.zona_manejo_id) ? window.filtros.zona_manejo_id : null;

            // 2) fallback: select de zonas
            if (!zonaId) zonaId = $('#zonas').val();

            // 3) fallback: input hidden (si existe)
            if (!zonaId) zonaId = $('#zona_manejo_id').val();

            // 4) fallback: querystring
            if (!zonaId) zonaId = new URLSearchParams(window.location.search).get('zona_manejo_id');

            return zonaId && zonaId !== "0" ? zonaId : null;
        }

        function renderEstado(msg) {
            $tbody.html(`
            <tr>
                <td colspan="4" class="text-center text-muted">${msg}</td>
            </tr>
            `);
        }

        function normalizeRow(r) {
            // Acepta varias formas típicas de respuesta del API
            const correctivo = r.correctivo ?? r.nombre ?? r.correctivo_nombre ?? '';
            const cantidad = r.cantidad ?? r.cantidad_sugerida ?? '';
            const unidades = r.unidades ?? r.unidad_medida ?? '';
            const efecto = r.efecto ?? r.efecto_esperado ?? '';
            return { correctivo, cantidad, unidades, efecto };
        }

        function renderTabla(rows) {
            if (!rows || rows.length === 0) {
            renderEstado('No hay correctivos para el año seleccionado.');
            return;
            }

            const html = rows.map((raw) => {
            const r = normalizeRow(raw);
            return `
                <tr>
                <td>${r.correctivo}</td>
                <td>${r.cantidad}</td>
                <td>${r.unidades}</td>
                <td>${r.efecto}</td>
                </tr>
            `;
            }).join('');

            $tbody.html(html);
        }

        async function cargarCorrectivos({ anio = null } = {}) {
            const zonaManejoId = getZonaManejoId();

            if (!zonaManejoId) {
            renderEstado('Selecciona una zona de manejo para mostrar correctivos.');
            return;
            }

            // Si no eligió año, muestra mensaje (o si quieres, puedes traer "todos" sin anio)
            if (!anio) {
            renderEstado('Selecciona un año para mostrar los correctivos sugeridos.');
            return;
            }

            // Abort request anterior
            if (abortController) abortController.abort();
            abortController = new AbortController();

            // UI loading (tu loader global)
            if (typeof mostrarLoader === 'function') mostrarLoader('Cargando correctivos...');
            renderEstado('Cargando correctivos...');

            try {
            const params = new URLSearchParams();
            params.set('zona_manejo_id', zonaManejoId);
            params.set('anio', anio);

            const url = `/api/correctivos/${encodeURIComponent(zonaManejoId)}/${encodeURIComponent(anio)}`;
            
            const resp = await fetch(url, {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                signal: abortController.signal
            });

            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

            const json = await resp.json();

            // Soporta respuestas tipo: []  ó {data: []} ó {correctivos: []}
            const rows = Array.isArray(json) ? json : (json.data ?? json.correctivos ?? []);
            renderTabla(rows);
            } catch (e) {
            // Si fue abort, no hagas nada
            if (e.name === 'AbortError') return;

            console.error('Error cargando correctivos:', e);
            renderEstado('Error al cargar correctivos. Intenta nuevamente.');
            } finally {
            if (typeof ocultarLoader === 'function') ocultarLoader();
            }
        }

        // Listener: cambio de año => consulta API
        $selectAnio.on('change', function () {
            const anio = this.value || null;
            cargarCorrectivos({ anio });
        });

            // Estado inicial: si ya viene un año seleccionado, auto-carga
            const anioInicial = $selectAnio.val();
            if (anioInicial) {
                cargarCorrectivos({ anio: anioInicial });
            } else {
                renderEstado('Selecciona un año para mostrar los correctivos sugeridos.');
            }
        }

        function initFertilidad() {

            function getZonaManejoId() {
                // 1) desde tus filtros globales
                let zonaId = (window.filtros && window.filtros.zona_manejo_id) ? window.filtros.zona_manejo_id : null;

                // 2) fallback: select de zonas
                if (!zonaId) zonaId = $('#zonas').val();

                // 3) fallback: input hidden (si existe)
                if (!zonaId) zonaId = $('#zona_manejo_id').val();

                // 4) fallback: querystring
                if (!zonaId) zonaId = new URLSearchParams(window.location.search).get('zona_manejo_id');

                return zonaId && zonaId !== "0" ? zonaId : null;
            }
            // Inicializa los elementos para cargar fertilidad (similar a correctivos, puedes unificar si quieres)
            $tbody = $('#tablaFertilidadBody');
            if (!$tbody.length) return;

            //Se genera la consulta
            const zonaManejoId = getZonaManejoId();
            if (!zonaManejoId) {
                renderEstado('Selecciona una zona de manejo para mostrar fertilidad.');
                return;
            }

            //Se generan los parámetros, solo la zona id
            const params = new URLSearchParams();
            params.set('zona_manejo_id', zonaManejoId);

            //url de la consulta, por ejemplo: /api/fertilidad?zona_manejo_id=123
            const url = `/api/fertilidad?${params.toString()}`;
            console.log('Cargando fertilidad con URL:', url);
            //UI loading
            //if (typeof mostrarLoader === 'function') mostrarLoader('Cargando fertilidad...');
            // $tbody.html(`
            //     <tr></tr>
            //         <td colspan="4" class="text-center text-muted">
            //             <div class="spinner-border text-primary" role="status">
            //                 <span class="sr-only">Cargando...</span>
            //             </div>
            //             <p class="mt-2">Cargando fertilidad...</p>
            //         </td>
            //     </tr>
            // `); 
            

            fetch(url, {
                method: 'GET',
                headers: { 'Accept': 'application/json' }
            })
            .then(resp => {
                if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
                return resp.json();
            })
            .then(json => {
                // Soporta respuestas tipo: []  ó {data: []} ó {fertilidad: []}
                const rows = Array.isArray(json) ? json : (json.data ?? json.fertilidad ?? []);
                if (!rows || rows.length === 0) {
                    $tbody.html(`
                        <tr>
                            <td colspan="4" class="text-center text-muted">No hay datos de fertilidad para esta zona de manejo.</td>
                        </tr>
                    `);
                    return;
                }

                // Renderizar filas de datos
                $tbody.html(rows.map(row => `
                    <tr>
                        <td>${row.descripcion}</td>
                        <td>${row.icp}</td>
                        <td>${row.resultado}</td>
                        <td>${row.Ponderacion}</td>
                        <td>${row.Restriccion}</td>
                        <td>${row.Nivel}</td>

                    </tr>
                `).join(''));
            })
            .catch(error => {
                console.error('Error cargando fertilidad:', error);
                $tbody.html(`
                    <tr>
                        <td colspan="4" class="text-center text-muted">Error al cargar fertilidad. Intenta nuevamente.</td>
                    </tr>
                `);
            });
        }
        </script>
        <script>
            $(document).ready(function() {
                ocultarLoader();
                activateTab();
                getTab();
                initTab8SueloCorrectivos();
                //Funcion para cargar fertilidad
                initFertilidad();
            });
        </script>
    @endsection

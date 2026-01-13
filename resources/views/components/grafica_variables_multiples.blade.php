@props(['zonaManejoId', 'periodo', 'startDate', 'endDate'])

<!-- Gráfica de Variables Múltiples -->
<div class="card shadow-lg border-0">
    <div class="card-header header-elements-inline bg-gradient-primary text-white">
        <h3 class="card-title mb-0">
            <i class="icon-graph mr-2"></i>
            Gráfica de Variables Múltiples
        </h3>
        <div class="header-elements">
            <div class="list-icons">
                <a class="list-icons-item" data-action="collapse"></a>
                <a class="list-icons-item" data-action="reload"></a>
                <a class="list-icons-item" data-action="remove"></a>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Controles de selección -->
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="col-form-label">Variables de medición <span class="text-danger">*</span></label>
                    <select class="form-control multiselect-select-all-filtering" id="variables_grafica"
                        name="variables_grafica[]" multiple="multiple" data-fouc>
                        <option value="temperatura">Temperatura (°C)</option>
                        <option value="humedad_relativa">Humedad Relativa (%)</option>
                        <option value="radiacion_solar">Radiación Solar (W/m²)</option>
                        <option value="precipitacion_acumulada">Precipitación Acumulada (mm)</option>
                        <option value="velocidad_viento">Velocidad del Viento (m/s)</option>
                        <option value="direccion_viento">Dirección del Viento (°)</option>
                        <option value="co2">CO2 (ppm)</option>
                        <option value="ph">pH</option>
                        <option value="phos">Fósforo (ppm)</option>
                        <option value="nit">Nitrógeno (ppm)</option>
                        <option value="pot">Potasio (ppm)</option>
                        <option value="temperatura_suelo">Temperatura del Suelo (°C)</option>
                        <option value="conductividad_electrica">Conductividad Eléctrica (Ds/m)</option>
                        <option value="potencial_de_hidrogeno">Potencial de Hidrógeno</option>
                        <option value="viento">Viento (m/s)</option>
                        <option value="humedad_15">Humedad 15 (%)</option>
                        <option value="temperatura_lvl1">Temperatura Nivel 1 (°C)</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="col-form-label">Tipos de agrupación <span class="text-danger">*</span></label>
                    <select class="form-control multiselect-select-all-filtering" id="agrupaciones_grafica"
                        name="agrupaciones_grafica[]" multiple="multiple" data-fouc>
                        <option value="max|Máximo">Máximo</option>
                        <option value="min|Mínimo">Mínimo</option>
                        <option value="avg|Promedio">Promedio</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Botones de control -->
        <div class="row mb-3">
            <div class="col-12">
                <button type="button" class="btn btn-success" id="generar-grafica-variables">
                    <i class="icon-chart"></i> Generar Gráfica
                </button>
                <button type="button" class="btn btn-outline-secondary ml-2" id="limpiar-grafica-variables">
                    <i class="icon-cross2"></i> Limpiar Selección
                </button>
            </div>
        </div>

        <!-- Gráfica principal de CO2 -->
        <div class="card shadow-lg border-0">
            <div class="card-header header-elements-inline bg-gradient-dark text-white">
                <h3 class="card-title mb-0">
                    <i class="icon-graph mr-2"></i>
                    Gráfica de Variables Múltiples
                </h3>
                <div class="header-elements">
                    <div class="list-icons">
                        <a class="list-icons-item" data-action="collapse"></a>
                        <a class="list-icons-item" data-action="reload"></a>
                        <a class="list-icons-item" data-action="remove"></a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="chart-container">
                    <div class="chart has-fixed-height" id="componente_grafica_variables_multiples"></div>
                </div>
            </div>
        </div>
        <!-- /gráfica principal -->

    </div>
</div>

<style>
    .chart-container {
        position: relative;
        width: 100%;
        height: 400px;
    }

    .chart {
        width: 100%;
        height: 100%;
    }

    #componente_grafica_variables_multiples {
        min-height: 400px;
        width: 100%;
    }

    .bg-gradient-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    }

    .card.shadow-lg {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card.shadow-lg:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1) !important;
    }

    .card-header h3 {
        font-weight: 600;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
    }
</style>

<!-- Scripts necesarios -->
@push('scripts')
    <script src="{{ url('js/charts/variables_multiples.js') }}"></script>
    <script>
        console.log('Componente de variables múltiples cargado');

        // Verificar que los scripts estén disponibles
        function verificarScripts() {
            if (typeof window.GraficaVariablesMultiples === 'undefined') {
                console.error('GraficaVariablesMultiples no está disponible. Esperando a que se cargue...');
                return false;
            }
            console.log('Scripts cargados correctamente');
            return true;
        }

        // Función para esperar a que los scripts estén disponibles
        function esperarScripts(callback, maxAttempts = 10) {
            let attempts = 0;

            function checkScripts() {
                attempts++;
                if (verificarScripts()) {
                    callback();
                } else if (attempts < maxAttempts) {
                    console.log(`Intento ${attempts}: Esperando scripts...`);
                    setTimeout(checkScripts, 200);
                } else {
                    console.error('No se pudieron cargar los scripts después de varios intentos');
                }
            }

            checkScripts();
        }

        function cargarGraficaVariablesMultiples(zona, periodo, startDate, endDate, variables, agrupaciones) {
            console.log('Intentando cargar gráfica de variables múltiples', {
                zona,
                periodo,
                startDate,
                endDate,
                variables,
                agrupaciones
            });

            if (!verificarScripts()) {
                console.log('Scripts no disponibles');
                return;
            }

            // Inicializar el módulo
            window.GraficaVariablesMultiples.init();

            const params = {
                variables: variables,
                agrupaciones: agrupaciones,
                estacion_id: zona,
                periodo: periodo,
                startDate: startDate,
                endDate: endDate
            };

            console.log('Enviando parámetros:', params);

            // Usar cargarDatos directamente (maneja todo internamente)
            window.GraficaVariablesMultiples.cargarDatos('componente_grafica_variables_multiples', params);
        }

        // Función para cargar las gráficas cuando el componente se monte
        function cargarGraficasVariablesMultiples() {
            const zona = {{ $zonaManejoId ?? 'null' }};
            const periodo = {{ $periodo ?? 'null' }};
            const startDate = '{{ $startDate ?? '' }}';
            const endDate = '{{ $endDate ?? '' }}';

            // Variables por defecto
            const variables = ['temperatura', 'humedad_relativa'];
            const agrupaciones = ['max|Máximo', 'min|Mínimo', 'avg|Promedio'];

            // Cargar gráfica de variables múltiples
            cargarGraficaVariablesMultiples(zona, periodo, startDate, endDate, variables, agrupaciones);
        }

        // Cargar las gráficas cuando el componente se monte
        document.addEventListener('DOMContentLoaded', function() {
            // Esperar a que los scripts estén disponibles antes de cargar las gráficas
            esperarScripts(function() {
                cargarGraficasVariablesMultiples();
            });
        });

        // También exponer la función globalmente para poder llamarla desde fuera
        window.cargarGraficasVariablesMultiples = cargarGraficasVariablesMultiples;
        window.cargarGraficaVariablesMultiples = cargarGraficaVariablesMultiples;

        // Inicializar multiselects y eventos cuando el DOM esté listo
        $(document).ready(function() {
            console.log('DOM listo, inicializando multiselects...');

            // Verificar que multiselect esté disponible
            if (typeof $.fn.multiselect === 'undefined') {
                console.error('Multiselect plugin no está disponible');
                return;
            }

            console.log('Multiselect disponible, inicializando...');

            // Inicializar multiselects
            $('#variables_grafica').multiselect({
                enableFiltering: true,
                enableCaseInsensitiveFiltering: true,
                filterPlaceholder: 'Buscar variables...',
                maxHeight: 300,
                buttonWidth: '100%',
                templates: {
                    button: '<button type="button" class="multiselect dropdown-toggle form-control" data-toggle="dropdown"><span class="multiselect-selected-text"></span> <b class="caret"></b></button>',
                    ul: '<ul class="multiselect-container dropdown-menu"></ul>',
                    filter: '<li class="multiselect-item filter"><div class="input-group"><span class="input-group-addon"><i class="icon-search4"></i></span><input class="form-control multiselect-search" type="text"></div></li>',
                    filterClearBtn: '<span class="input-group-btn"><button class="btn btn-default multiselect-clear-filter" type="button"><i class="icon-cross2"></i></button></span>',
                    li: '<li><a tabindex="0"><label></label></a></li>',
                    divider: '<li class="multiselect-item divider"></li>',
                    liGroup: '<li class="multiselect-item multiselect-group"><label></label></li>'
                }
            });

            $('#agrupaciones_grafica').multiselect({
                enableFiltering: true,
                enableCaseInsensitiveFiltering: true,
                filterPlaceholder: 'Buscar agrupaciones...',
                maxHeight: 300,
                buttonWidth: '100%',
                templates: {
                    button: '<button type="button" class="multiselect dropdown-toggle form-control" data-toggle="dropdown"><span class="multiselect-selected-text"></span> <b class="caret"></b></button>',
                    ul: '<ul class="multiselect-container dropdown-menu"></ul>',
                    filter: '<li class="multiselect-item filter"><div class="input-group"><span class="input-group-addon"><i class="icon-search4"></i></span><input class="form-control multiselect-search" type="text"></div></li>',
                    filterClearBtn: '<span class="input-group-btn"><button class="btn btn-default multiselect-clear-filter" type="button"><i class="icon-cross2"></i></button></span>',
                    li: '<li><a tabindex="0"><label></label></a></li>',
                    divider: '<li class="multiselect-item divider"></li>',
                    liGroup: '<li class="multiselect-item multiselect-group"><label></label></li>'
                }
            });

            console.log('Multiselects inicializados correctamente');

            // Seleccionar variables por defecto
            $('#variables_grafica').multiselect('select', ['temperatura', 'humedad_relativa']);
            $('#agrupaciones_grafica').multiselect('select', ['max|Máximo', 'min|Mínimo', 'avg|Promedio']);

            console.log('Variables por defecto seleccionadas');

            // Manejar botón de generar gráfica
            $('#generar-grafica-variables').on('click', function() {
                const variables = $('#variables_grafica').val();
                const agrupaciones = $('#agrupaciones_grafica').val();

                if (!variables || variables.length === 0) {
                    alert('Por favor selecciona al menos una variable');
                    return;
                }

                if (!agrupaciones || agrupaciones.length === 0) {
                    alert('Por favor selecciona al menos una agrupación');
                    return;
                }

                const zona = {{ $zonaManejoId ?? 'null' }};
                const periodo = {{ $periodo ?? 'null' }};
                const startDate = '{{ $startDate ?? '' }}';
                const endDate = '{{ $endDate ?? '' }}';

                if (!zona) {
                    alert('Por favor selecciona una zona de manejo');
                    return;
                }

                cargarGraficaVariablesMultiples(zona, periodo, startDate, endDate, variables, agrupaciones);
            });

            // Manejar botón de limpiar
            $('#limpiar-grafica-variables').on('click', function() {
                $('#variables_grafica').multiselect('deselectAll', false);
                $('#agrupaciones_grafica').multiselect('deselectAll', false);
                if (typeof window.GraficaVariablesMultiples !== 'undefined') {
                    window.GraficaVariablesMultiples.limpiar('componente_grafica_variables_multiples');
                }
            });
        });
    </script>
@endpush

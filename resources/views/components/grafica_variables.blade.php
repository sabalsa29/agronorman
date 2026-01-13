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
    </div>
</div>
<div>
    <!-- Título principal -->
    <div class="text-center mb-4">
        <h1 class="display-4 text-dark font-weight-bold">
            <i class="icon-leaf mr-2"></i>
            CO₂ Atmosférico
        </h1>
        <p class="lead text-muted">Monitoreo y análisis del dióxido de carbono en el aire</p>
    </div>

    <!-- Gráfica principal de CO2 -->
    <div class="card shadow-lg border-0">
        <div class="card-header header-elements-inline bg-gradient-dark text-white">
            <h3 class="card-title mb-0">
                <i class="icon-graph mr-2"></i>
                Gráfica de CO₂ Atmosférico
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
                <div class="chart has-fixed-height" id="grafica_co2"></div>
            </div>
        </div>
    </div>
    <!-- /gráfica principal -->


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

        #columns_stacked_co2 {
            min-height: 400px;
            width: 100%;
        }

        /* Estilos para títulos más vistosos */
        .display-4 {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            letter-spacing: 1px;
        }

        .bg-gradient-dark {
            background: linear-gradient(135deg, #343a40 0%, #212529 100%);
        }

        .bg-gradient-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
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

    <script>
        console.log('Componente de CO2 cargado....');

        // Verificar que los scripts estén disponibles
        function verificarScripts() {
            if (typeof window.EchartsAreas === 'undefined') {
                console.error('EchartsAreas no está disponible. Esperando a que se cargue...');
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

        function cargaGraficaCO2(zona, periodo, startDate, endDate) {
            console.log('Intentando cargar gráfica de CO2', {
                zona,
                periodo,
                startDate,
                endDate
            });
            if (!verificarScripts()) {
                console.log('Scripts no disponibles');
                return;
            }

            window.EchartsAreas.init();

            const params = {
                estacion_id: zona,
                periodo: periodo,
                startDate: startDate,
                endDate: endDate
            };

            console.log('Enviando parámetros para CO2:', params);

            return axios.get("{{ route('home.grafica_co2') }}", {
                    params: params
                })
                .then(function(response) {
                    let data = response.data;
                    console.log('Datos de CO2 recibidos:', data);

                    // Actualizar la gráfica de CO2 usando la función existente de areas.js
                    window.EchartsAreas.updateAreaZoom('grafica_co2', data.labels, data
                        .maximosCo2, data
                        .minimosCo2, data
                        .promediosCo2);

                    // Mensaje visual si no se renderiza nada
                    setTimeout(function() {
                        const grafDiv = document.getElementById('grafica_co2');
                        if (grafDiv && !grafDiv.innerHTML.trim()) {
                            grafDiv.innerHTML =
                                '<div class="alert alert-warning">No se pudo renderizar la gráfica de CO2.</div>';
                        }
                    }, 1000);
                })
                .catch(function(error) {
                    console.error('Error loading CO2 chart:', error);
                    const grafDiv = document.getElementById('grafica_co2');
                    if (grafDiv) {
                        if (error.response) {
                            console.error('Error response:', error.response.data);
                            if (error.response.data && error.response.data.message) {
                                grafDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.response.data
                                    .message + '</div>';
                            } else {
                                grafDiv.innerHTML =
                                    '<div class="alert alert-danger">Error al cargar los datos de CO2. Código: ' + error
                                    .response.status + '</div>';
                            }
                        } else {
                            grafDiv.innerHTML =
                                '<div class="alert alert-danger">Error de conexión al cargar los datos de CO2.</div>';
                        }
                    }
                });
        }

        // Función para cargar las gráficas cuando el componente se monte
        function cargarGraficasCO2() {
            const zona = '{{ $zonaManejo->id ?? '' }}';
            const periodo = '{{ $periodo }}';
            const startDate = '{{ $startDate }}';
            const endDate = '{{ $endDate }}';
            // Cargar gráfica de CO2
            cargaGraficaCO2(zona, periodo, startDate, endDate);
        }
        window.cargarGraficasCO2 = cargarGraficasCO2;
    </script>
</div>

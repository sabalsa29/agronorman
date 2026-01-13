<div>
    <!-- Título principal -->
    <div class="text-center mb-4">
        <h1 class="display-4 text-danger font-weight-bold">
            <i class="icon-thermometer mr-2"></i>
            Temperatura Atmosférica
        </h1>
        <p class="lead text-muted">Monitoreo y análisis de la temperatura del aire</p>
    </div>

    <!-- Gráfica principal de temperatura -->
    <div class="card shadow-lg border-0">
        <div class="card-header header-elements-inline bg-gradient-danger text-white">
            <h3 class="card-title mb-0">
                <i class="icon-graph mr-2"></i>
                Gráfica de Temperatura Atmosférica (°C)
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
                <div class="chart has-fixed-height" id="grafica_temperatura"></div>
            </div>
        </div>
    </div>
    <!-- /gráfica principal -->

    <!-- Gráfica de estrés histórico -->
    <div class="card shadow-lg border-0 mt-4">
        <div class="card-header header-elements-inline bg-gradient-warning text-white">
            <div class="d-flex flex-column align-items-start">
                <h3 class="card-title mb-0" id="titulo_estres_temperatura">
                    <i class="icon-stats-bars mr-2"></i>
                    Análisis de Estrés por Temperatura - Histórico
                </h3>
                <div id="info_estres_temperatura" class="estres-info mt-2" style="font-size: 0.875rem; opacity: 0.9;"></div>
            </div>
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
                <div class="chart has-fixed-height" id="columns_stacked_temperatura"></div>
            </div>
        </div>
    </div>
    <!-- /gráfica de estrés histórico -->

    <!-- Gráfica de estrés pronóstico -->
    <div class="card shadow-lg border-0 mt-4">
        <div class="card-header header-elements-inline bg-gradient-info text-white">
            <h3 class="card-title mb-0">
                <i class="icon-calendar mr-2"></i>
                Análisis de Estrés por Temperatura - Pronóstico
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
                <div class="chart has-fixed-height" id="columns_stacked_pronostico"></div>
            </div>
        </div>
    </div>
    <!-- /gráfica de estrés pronóstico -->

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

        #columns_clustered {
            min-height: 400px;
            width: 100%;
        }

        #columns_stacked_temperatura {
            min-height: 400px;
            width: 100%;
        }

        #columns_stacked_pronostico {
            min-height: 400px;
            width: 100%;
        }

        /* Estilos para títulos más vistosos */
        .display-4 {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            letter-spacing: 1px;
        }

        .bg-gradient-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }

        .bg-gradient-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
        }

        .bg-gradient-info {
            background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);
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
        console.log('Componente de temperatura atmosférica cargado');

        // Verificar que los scripts estén disponibles
        function verificarScripts() {
            if (typeof window.EchartsAreas === 'undefined') {
                console.error('EchartsAreas no está disponible. Esperando a que se cargue...');
                return false;
            }
            if (typeof window.EchartsColumnsWaterfallsTemperatura === 'undefined') {
                console.error('EchartsColumnsWaterfallsTemperatura no está disponible. Esperando a que se cargue...');
                return false;
            }
            if (typeof window.EchartsColumnsWaterfallsPronosticoTemperatura === 'undefined') {
                console.error(
                    'EchartsColumnsWaterfallsPronosticoTemperatura no está disponible. Esperando a que se cargue...');
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

        function cargaGraficaTemperatura(zona, periodo, startDate, endDate) {
            console.log('Intentando cargar gráfica de temperatura', {
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

            console.log('Enviando parámetros:', params);

            return axios.get("{{ route('home.grafica_temperatura') }}", {
                    params: params
                })
                .then(function(response) {
                    let data = response.data;
                    console.log('Datos recibidos:', data);
                    window.EchartsAreas.updateAreaZoom('grafica_temperatura', data.labels, data
                        .maximosTemperatura, data
                        .minimosTemperatura, data
                        .promediosTemperatura);

                    // Mensaje visual si no se renderiza nada
                    setTimeout(function() {
                        const grafDiv = document.getElementById('grafica_temperatura');
                        if (grafDiv && !grafDiv.innerHTML.trim()) {
                            grafDiv.innerHTML =
                                '<div class="alert alert-warning">No se pudo renderizar la gráfica.</div>';
                        }
                    }, 1000);
                })
                .catch(function(error) {
                    console.error('Error loading temperature chart:', error);
                    const errorDiv = document.getElementById('error-message');
                    if (error.response) {
                        console.error('Error response:', error.response.data);
                        if (error.response.data && error.response.data.message) {
                            errorDiv.textContent = 'Error: ' + error.response.data.message;
                        } else {
                            errorDiv.textContent =
                                'Error al cargar los datos de temperatura. Código: ' + error.response
                                .status;
                        }
                    } else {
                        errorDiv.textContent = 'Error de conexión al cargar los datos de temperatura.';
                    }
                    errorDiv.style.display = 'block';
                });
        }

        function cargaGraficaEstres(zona, periodo, startDate, endDate) {
            if (!verificarScripts()) {
                return;
            }

            const params = {
                estacion_id: zona,
                periodo: periodo,
                startDate: startDate,
                endDate: endDate,
                tipo_cultivo_id: "{{ $tipoCultivoId ?? '' }}",
                etapa_fenologica_id: "{{ $etapaFenologicaId ?? '' }}",
                variable: "temperatura"
            };

            console.log('Loading historical stress data with params:', params);

            // Cargar datos históricos
            return axios.get("{{ route('home.grafica_estres') }}", {
                    params: params
                })
                .then(function(response) {
                    console.log('Historical stress data response:', response.data);
                    window.EchartsColumnsWaterfallsTemperatura.updateData(response.data);
                })
                .catch(function(error) {
                    console.error('Error loading historical stress data:', error);
                    if (error.response) {
                        console.error('Error response:', error.response.data);
                    }
                });
        }

        function cargaGraficaEstresPronostico(zona, periodo, startDate, endDate) {
            if (!verificarScripts()) {
                return;
            }

            const params = {
                estacion_id: zona,
                periodo: periodo,
                startDate: startDate,
                endDate: endDate,
                tipo_cultivo_id: "{{ $tipoCultivoId ?? '' }}",
                etapa_fenologica_id: "{{ $etapaFenologicaId ?? '' }}",
                variable: "temperatura"
            };

            console.log('Loading forecast stress data with params:', params);

            // Cargar datos de pronóstico
            return axios.get("{{ route('home.grafica_estres_pronostico') }}", {
                    params: params
                })
                .then(function(response) {
                    console.log('Forecast stress data response:', response.data);
                    window.EchartsColumnsWaterfallsPronosticoTemperatura.updatePronosticoData(response.data);
                })
                .catch(function(error) {
                    console.error('Error loading forecast stress data:', error);
                    if (error.response) {
                        console.error('Error response:', error.response.data);
                    }
                });
        }

        // Función para cargar las gráficas cuando el componente se monte
        function cargarGraficasTemperaturaAtmosferica() {
            const zona = '{{ $zonaManejo->id ?? '' }}';
            const periodo = '{{ $periodo }}';
            const startDate = '{{ $startDate }}';
            const endDate = '{{ $endDate }}';

            // Cargar gráfica de temperatura
            cargaGraficaTemperatura(zona, periodo, startDate, endDate);

            // Inicializar la gráfica de estrés
            window.EchartsColumnsWaterfallsTemperatura.init();
            // Inicializar la gráfica de pronóstico de estrés
            window.EchartsColumnsWaterfallsPronosticoTemperatura.init();

            // Cargar datos históricos de estrés
            cargaGraficaEstres(zona, periodo, startDate, endDate);
            // Cargar datos de pronóstico de estrés
            cargaGraficaEstresPronostico(zona, periodo, startDate, endDate);
        }

        // Cargar las gráficas cuando el componente se monte
        document.addEventListener('DOMContentLoaded', function() {
            // Esperar a que los scripts estén disponibles antes de cargar las gráficas
            esperarScripts(function() {
                cargarGraficasTemperaturaAtmosferica();
            });
        });

        // También exponer la función globalmente para poder llamarla desde fuera
        window.cargarGraficasTemperaturaAtmosferica = cargarGraficasTemperaturaAtmosferica;
        window.cargaGraficaEstres = cargaGraficaEstres;
        window.cargaGraficaEstresPronostico = cargaGraficaEstresPronostico;
    </script>
</div>

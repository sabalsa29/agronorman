<div>
    <!-- Título principal -->
    <div class="text-center mb-4">
        <h1 class="display-4 text-primary font-weight-bold">
            <i class="icon-droplet mr-2"></i>
            Humedad Relativa Atmosférica
        </h1>
        <p class="lead text-muted">Monitoreo y análisis de la humedad relativa del aire</p>
    </div>

    <!-- Stacked clustered columns -->
    <div class="card shadow-lg border-0">
        <div class="card-header header-elements-inline bg-gradient-primary text-white">
            <h3 class="card-title mb-0">
                <i class="icon-graph mr-2"></i>
                Gráfica de Humedad Relativa Atmosférica (%)
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
                <div class="chart has-fixed-height" id="grafica_humedad_relativa"></div>
            </div>
        </div>
    </div>
    <!-- /stacked clustered columns -->

    <!-- Stacked clustered columns para estrés de humedad relativa -->
    <div class="card shadow-lg border-0 mt-4">
        <div class="card-header header-elements-inline bg-gradient-warning text-white">
            <div class="d-flex flex-column align-items-start">
                <h3 class="card-title mb-0" id="titulo_estres_humedad_relativa">
                    <i class="icon-stats-bars mr-2"></i>
                    Análisis de Estrés por Humedad Relativa
                </h3>
                <div id="info_estres_humedad_relativa" class="estres-info mt-2" style="font-size: 0.875rem; opacity: 0.9;"></div>
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
                <div class="chart has-fixed-height" id="columns_stacked_humedad"></div>
            </div>
        </div>
    </div>
    <!-- /stacked clustered columns estrés humedad relativa -->

    <!-- Stacked clustered columns para pronóstico de estrés de humedad relativa -->
    <div class="card shadow-lg border-0 mt-4">
        <div class="card-header header-elements-inline bg-gradient-info text-white">
            <h3 class="card-title mb-0">
                <i class="icon-weather-cloudy mr-2"></i>
                Pronóstico de Estrés por Humedad Relativa
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
                <div class="chart has-fixed-height" id="columns_stacked_pronostico_humedad_relativa"></div>
            </div>
        </div>
    </div>
    <!-- /stacked clustered columns pronóstico estrés humedad relativa -->

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

        #columns_stacked_humedad {
            min-height: 400px;
            width: 100%;
        }

        #columns_stacked_humedad_pronostico {
            min-height: 400px;
            width: 100%;
        }

        /* Estilos para títulos más vistosos */
        .display-4 {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            letter-spacing: 1px;
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        }

        .bg-gradient-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
        }

        .bg-gradient-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
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
        console.log('Componente de humedad relativa cargado....');

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

        function cargaGraficaHumedadRelativa(zona, periodo, startDate, endDate) {
            console.log('Intentando cargar gráfica de humedad relativa', {
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

            console.log('Enviando parámetros para humedad relativa:', params);

            return axios.get("{{ route('home.grafica_humedad_relativa') }}", {
                    params: params
                })
                .then(function(response) {
                    let data = response.data;
                    console.log('Datos de humedad relativa recibidos:', data);

                    // Actualizar la gráfica de humedad relativa usando la función existente de areas.js
                    window.EchartsAreas.updateAreaZoom('grafica_humedad_relativa', data.labels, data
                        .maximosHumedad, data
                        .minimosHumedad, data
                        .promediosHumedad);

                    // Mensaje visual si no se renderiza nada
                    setTimeout(function() {
                        const grafDiv = document.getElementById('grafica_humedad_relativa');
                        if (grafDiv && !grafDiv.innerHTML.trim()) {
                            grafDiv.innerHTML =
                                '<div class="alert alert-warning">No se pudo renderizar la gráfica de humedad relativa.</div>';
                        }
                    }, 1000);
                })
                .catch(function(error) {
                    console.error('Error loading humedad relativa chart:', error);
                    const grafDiv = document.getElementById('grafica_humedad_relativa');
                    if (grafDiv) {
                        if (error.response) {
                            console.error('Error response:', error.response.data);
                            if (error.response.data && error.response.data.message) {
                                grafDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.response.data
                                    .message + '</div>';
                            } else {
                                grafDiv.innerHTML =
                                    '<div class="alert alert-danger">Error al cargar los datos de humedad relativa. Código: ' +
                                    error
                                    .response.status + '</div>';
                            }
                        } else {
                            grafDiv.innerHTML =
                                '<div class="alert alert-danger">Error de conexión al cargar los datos de humedad relativa.</div>';
                        }
                    }
                });
        }

        // Lógica para gráfica de estrés de humedad relativa
        function cargaGraficaEstresHumedad(zona, periodo, startDate, endDate) {
            if (typeof window.EchartsColumnsWaterfallsHumedadRelativa === 'undefined') {
                console.error('EchartsColumnsWaterfallsHumedadRelativa no está disponible. Esperando a que se cargue...');
                return;
            }
            const params = {
                estacion_id: zona,
                periodo: periodo,
                startDate: startDate,
                endDate: endDate,
                tipo_cultivo_id: "{{ $tipoCultivoId ?? '' }}",
                etapa_fenologica_id: "{{ $etapaFenologicaId ?? '' }}",
                variable: 'humedad_relativa'
            };
            console.log('Loading humedad relativa stress data with params:', params);
            return axios.get("{{ route('home.grafica_estres') }}", {
                    params: params
                })
                .then(function(response) {
                    console.log('Humedad relativa stress data response:', response.data);
                    if (typeof window.EchartsColumnsWaterfallsHumedadRelativa !== 'undefined') {
                        window.EchartsColumnsWaterfallsHumedadRelativa.updateData(response.data,
                            'columns_stacked_humedad');
                    }
                })
                .catch(function(error) {
                    console.error('Error loading humedad relativa stress data:', error);
                    if (error.response) {
                        console.error('Error response:', error.response.data);
                    }
                });
        }

        // Lógica para gráfica de pronóstico de estrés de humedad relativa
        function cargaGraficaPronosticoEstresHumedad(zona, periodo, startDate, endDate) {
            if (typeof window.EchartsColumnsWaterfallsPronosticoHumedadRelativa === 'undefined') {
                console.error(
                    'EchartsColumnsWaterfallsPronosticoHumedadRelativa no está disponible. Esperando a que se cargue...'
                );
                return;
            }
            const params = {
                estacion_id: zona,
                periodo: periodo,
                startDate: startDate,
                endDate: endDate,
                tipo_cultivo_id: "{{ $tipoCultivoId ?? '' }}",
                etapa_fenologica_id: "{{ $etapaFenologicaId ?? '' }}"
            };
            console.log('Loading humedad relativa forecast stress data with params:', params);
            return axios.get("{{ route('home.grafica_estres_pronostico_humedad_relativa') }}", {
                    params: params
                })
                .then(function(response) {
                    console.log('Humedad relativa forecast stress data response:', response.data);
                    if (typeof window.EchartsColumnsWaterfallsPronosticoHumedadRelativa !== 'undefined') {
                        window.EchartsColumnsWaterfallsPronosticoHumedadRelativa.updatePronosticoDataHumedadRelativa(
                            response.data);
                    }
                })
                .catch(function(error) {
                    console.error('Error loading humedad relativa forecast stress data:', error);
                    if (error.response) {
                        console.error('Error response:', error.response.data);
                    }
                });
        }

        // Función para cargar las gráficas cuando el componente se monte
        function cargarGraficasHumedadRelativa() {
            const zona = '{{ $zonaManejo->id ?? '' }}';
            const periodo = '{{ $periodo }}';
            const startDate = '{{ $startDate }}';
            const endDate = '{{ $endDate }}';
            // Cargar gráfica de humedad relativa
            cargaGraficaHumedadRelativa(zona, periodo, startDate, endDate);
            // Inicializar la gráfica de estrés de humedad relativa
            if (window.EchartsColumnsWaterfallsHumedadRelativa && window.EchartsColumnsWaterfallsHumedadRelativa.init) {
                window.EchartsColumnsWaterfallsHumedadRelativa.init('columns_stacked_humedad');
            }
            // Inicializar la gráfica de pronóstico de estrés de humedad relativa
            if (window.EchartsColumnsWaterfallsPronosticoHumedadRelativa && window
                .EchartsColumnsWaterfallsPronosticoHumedadRelativa.init) {
                window.EchartsColumnsWaterfallsPronosticoHumedadRelativa.init();
            }
            // Cargar datos históricos de estrés de humedad relativa
            setTimeout(function() {
                cargaGraficaEstresHumedad(zona, periodo, startDate, endDate);
            }, 500);
            // Cargar datos de pronóstico de estrés de humedad relativa
            setTimeout(function() {
                cargaGraficaPronosticoEstresHumedad(zona, periodo, startDate, endDate);
            }, 1000);
        }
        window.cargarGraficasHumedadRelativa = cargarGraficasHumedadRelativa;
        window.cargaGraficaEstresHumedad = cargaGraficaEstresHumedad;
        window.cargaGraficaPronosticoEstresHumedad = cargaGraficaPronosticoEstresHumedad;
    </script>
</div>

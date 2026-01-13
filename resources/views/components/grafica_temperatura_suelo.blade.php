<div>
    <!-- Título principal -->
    <div class="text-center mb-4">
        <h1 class="display-4 text-warning font-weight-bold">
            <i class="icon-thermometer mr-2"></i>
            Temperatura del Suelo
        </h1>
        <p class="lead text-muted">Monitoreo y análisis de la temperatura del suelo</p>
    </div>

    <!-- Gráfica principal de temperatura del suelo -->
    <div class="card shadow-lg border-0">
        <div class="card-header header-elements-inline bg-gradient-warning text-white">
            <h3 class="card-title mb-0">
                <i class="icon-graph mr-2"></i>
                Gráfica de Temperatura del Suelo (°C)
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
                <div class="chart has-fixed-height" id="grafica_temperatura_suelo"></div>
            </div>
        </div>
    </div>
    <!-- /gráfica principal -->

    <!-- Gráfica de estrés de temperatura del suelo -->
    <div class="card shadow-lg border-0 mt-4">
        <div class="card-header header-elements-inline bg-gradient-orange text-white">
            <div class="d-flex flex-column align-items-start">
                <h3 class="card-title mb-0" id="titulo_estres_temperatura_suelo">
                    <i class="icon-stats-bars mr-2"></i>
                    Análisis de Estrés por Temperatura del Suelo
                </h3>
                <div id="info_estres_temperatura_suelo" class="estres-info mt-2" style="font-size: 0.875rem; opacity: 0.9;"></div>
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
                <div class="chart has-fixed-height" id="columns_stacked_suelo"></div>
            </div>
        </div>
    </div>
    <!-- /gráfica de estrés -->

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

        /* Estilos para títulos más vistosos */
        .display-4 {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            letter-spacing: 1px;
        }

        .bg-gradient-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
        }

        .bg-gradient-orange {
            background: linear-gradient(135deg, #fd7e14 0%, #e55a00 100%);
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
        console.log('Componente de Temperatura del Suelo cargado');

        function verificarScripts() {
            if (typeof window.EchartsAreas === 'undefined') {
                console.error('EchartsAreas no está disponible. Esperando a que se cargue...');
                return false;
            }
            console.log('Scripts cargados correctamente');
            return true;
        }

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

        function cargaGraficaTemperaturaSuelo(zona, periodo, startDate, endDate) {
            console.log('Intentando cargar gráfica de temperatura del suelo', {
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

            console.log('Enviando parámetros para temperatura del suelo:', params);

            return axios.get("{{ route('home.grafica_temperatura_suelo') }}", {
                    params: params
                })
                .then(function(response) {
                    let data = response.data;
                    console.log('Datos de temperatura del suelo recibidos:', data);

                    window.EchartsAreas.updateAreaZoom('grafica_temperatura_suelo', data.labels, data
                        .maximosTemperaturaSuelo, data.minimosTemperaturaSuelo, data.promediosTemperaturaSuelo);
                    console.log('Datos de temperatura del suelo recibidos:', data);

                    setTimeout(function() {
                        const grafDiv = document.getElementById('grafica_temperatura_suelo');
                        if (grafDiv && !grafDiv.innerHTML.trim()) {
                            grafDiv.innerHTML =
                                '<div class="alert alert-warning">No se pudo renderizar la gráfica de temperatura del suelo.</div>';
                        }
                    }, 1000);
                })
                .catch(function(error) {
                    console.error('Error loading soil temperature chart:', error);
                    const grafDiv = document.getElementById('grafica_temperatura_suelo');
                    if (grafDiv) {
                        if (error.response) {
                            console.error('Error response:', error.response.data);
                            if (error.response.data && error.response.data.message) {
                                grafDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.response.data
                                    .message + '</div>';
                            } else {
                                grafDiv.innerHTML =
                                    '<div class="alert alert-danger">Error al cargar los datos de temperatura del suelo. Código: ' +
                                    error.response.status + '</div>';
                            }
                        } else {
                            grafDiv.innerHTML =
                                '<div class="alert alert-danger">Error de conexión al cargar los datos de temperatura del suelo.</div>';
                        }
                    }
                });
        }

        // Lógica para gráfica de estrés de temperatura del suelo
        function cargaGraficaEstresSuelo(zona, periodo, startDate, endDate) {
            if (typeof window.EchartsColumnsWaterfallsSuelo === 'undefined') {
                console.error('EchartsColumnsWaterfallsSuelo no está disponible. Esperando a que se cargue...');
                return;
            }
            const params = {
                estacion_id: zona,
                periodo: periodo,
                startDate: startDate,
                endDate: endDate,
                tipo_cultivo_id: "{{ $tipoCultivoId ?? '' }}",
                etapa_fenologica_id: "{{ $etapaFenologicaId ?? '' }}",
                variable: "temperatura_suelo"
            };
            console.log('Loading soil temperature stress data with params:', params);
            return axios.get("{{ route('home.grafica_estres') }}", {
                    params: params
                })
                .then(function(response) {
                    console.log('Soil temperature stress data response:', response.data);
                    window.EchartsColumnsWaterfallsSuelo.updateData(response.data, 'columns_stacked_suelo');
                })
                .catch(function(error) {
                    console.error('Error loading soil temperature stress data:', error);
                    if (error.response) {
                        console.error('Error response:', error.response.data);
                    }
                });
        }

        // Función para cargar ambas gráficas cuando el componente se monte
        function cargarGraficaTemperaturaSuelo() {
            const zona = '{{ $zonaManejo->id ?? '' }}';
            const periodo = '{{ $periodo }}';
            const startDate = '{{ $startDate }}';
            const endDate = '{{ $endDate }}';
            // Cargar gráfica principal
            cargaGraficaTemperaturaSuelo(zona, periodo, startDate, endDate);
            // Inicializar la gráfica de estrés de temperatura del suelo
            if (window.EchartsColumnsWaterfallsSuelo && window.EchartsColumnsWaterfallsSuelo.init) {
                window.EchartsColumnsWaterfallsSuelo.init('columns_stacked_suelo');
            }
            // Cargar datos históricos de estrés de temperatura del suelo
            cargaGraficaEstresSuelo(zona, periodo, startDate, endDate);
        }
        window.cargarGraficaTemperaturaSuelo = cargarGraficaTemperaturaSuelo;
        window.cargaGraficaEstresSuelo = cargaGraficaEstresSuelo;
    </script>
</div>

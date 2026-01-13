<div>
    <!-- Título principal -->
    <div class="text-center mb-4">
        <h1 class="display-4 text-success font-weight-bold">
            <i class="icon-earth mr-2"></i>
            Humedad del Suelo
        </h1>
        <p class="lead text-muted">Monitoreo y análisis de la humedad del suelo</p>
    </div>

    <!-- Stacked clustered columns -->
    <div class="card shadow-lg border-0">
        <div class="card-header header-elements-inline bg-gradient-success text-white">
            <h3 class="card-title mb-0">
                <i class="icon-graph mr-2"></i>
                Gráfica de Humedad del Suelo (%)
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
                <div class="chart has-fixed-height" id="grafica_humedad_suelo"></div>
            </div>
        </div>
    </div>
    <!-- /stacked clustered columns -->

    <!-- Stacked clustered columns para estrés de humedad del suelo -->
    <div class="card shadow-lg border-0 mt-4">
        <div class="card-header header-elements-inline bg-gradient-info text-white">
            <div class="d-flex flex-column align-items-start">
                <h3 class="card-title mb-0" id="titulo_estres_humedad_suelo">
                    <i class="icon-stats-bars mr-2"></i>
                    Análisis de Estrés por Humedad del Suelo
                </h3>
                <div id="info_estres_humedad_suelo" class="estres-info mt-2" style="font-size: 0.875rem; opacity: 0.9;"></div>
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
                <div class="chart has-fixed-height" id="columns_stacked_humedad_suelo"></div>
            </div>
        </div>
    </div>
    <!-- /stacked clustered columns estrés humedad del suelo -->

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

        #columns_stacked_humedad_suelo {
            min-height: 400px;
            width: 100%;
        }

        /* Estilos para títulos más vistosos */
        .display-4 {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            letter-spacing: 1px;
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
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
        console.log('Componente de humedad del suelo cargado....');

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

        function cargaGraficaHumedadSuelo(zona, periodo, startDate, endDate) {
            console.log('Intentando cargar gráfica de humedad del suelo', {
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

            console.log('Enviando parámetros para humedad del suelo:', params);

            return axios.get("{{ route('home.grafica_humedad_suelo') }}", {
                    params: params
                })
                .then(function(response) {
                    let data = response.data;
                    console.log('Datos de humedad del suelo recibidos:', data);

                    // Verificar si el elemento existe
                    const grafDiv = document.getElementById('grafica_humedad_suelo');
                    if (!grafDiv) {
                        console.error('Elemento grafica_humedad_suelo no encontrado');
                        return;
                    }

                    // Verificar si EchartsAreas.updateAreaZoom existe
                    if (typeof window.EchartsAreas.updateAreaZoom !== 'function') {
                        console.error('EchartsAreas.updateAreaZoom no es una función');
                        grafDiv.innerHTML =
                            '<div class="alert alert-danger">Error: Función updateAreaZoom no disponible</div>';
                        return;
                    }

                    // Actualizar la gráfica de humedad del suelo usando la función existente de areas.js
                    try {
                        window.EchartsAreas.updateAreaZoom('grafica_humedad_suelo', data.labels, data
                            .maximosHumedadSuelo, data
                            .minimosHumedadSuelo, data
                            .promediosHumedadSuelo);
                        console.log('Gráfica actualizada exitosamente');
                    } catch (error) {
                        console.error('Error al actualizar la gráfica:', error);
                        grafDiv.innerHTML = '<div class="alert alert-danger">Error al actualizar la gráfica: ' + error
                            .message + '</div>';
                    }

                    // Mensaje visual si no se renderiza nada
                    setTimeout(function() {
                        if (grafDiv && !grafDiv.innerHTML.trim()) {
                            grafDiv.innerHTML =
                                '<div class="alert alert-warning">No se pudo renderizar la gráfica de humedad del suelo.</div>';
                        }
                    }, 1000);
                })
                .catch(function(error) {
                    console.error('Error loading humedad del suelo chart:', error);
                    const grafDiv = document.getElementById('grafica_humedad_suelo');
                    if (grafDiv) {
                        if (error.response) {
                            console.error('Error response:', error.response.data);
                            if (error.response.data && error.response.data.message) {
                                grafDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.response.data
                                    .message + '</div>';
                            } else {
                                grafDiv.innerHTML =
                                    '<div class="alert alert-danger">Error al cargar los datos de humedad del suelo. Código: ' +
                                    error
                                    .response.status + '</div>';
                            }
                        } else {
                            grafDiv.innerHTML =
                                '<div class="alert alert-danger">Error de conexión al cargar los datos de humedad del suelo.</div>';
                        }
                    }
                });
        }

        // Lógica para gráfica de estrés de humedad del suelo
        function cargaGraficaEstresHumedadSuelo(zona, periodo, startDate, endDate) {
            if (typeof window.EchartsColumnsWaterfallsHumedadSuelo === 'undefined') {
                console.error('EchartsColumnsWaterfallsHumedadSuelo no está disponible. Esperando a que se cargue...');
                return;
            }
            const params = {
                estacion_id: zona,
                periodo: periodo,
                startDate: startDate,
                endDate: endDate,
                tipo_cultivo_id: "{{ $tipoCultivoId ?? '' }}",
                etapa_fenologica_id: "{{ $etapaFenologicaId ?? '' }}",
                variable: 'humedad_15'
            };
            console.log('Loading humedad del suelo stress data with params:', params);
            return axios.get("{{ route('home.grafica_estres') }}", {
                    params: params
                })
                .then(function(response) {
                    console.log('Humedad del suelo stress data response:', response.data);
                    if (typeof window.EchartsColumnsWaterfallsHumedadSuelo !== 'undefined') {
                        window.EchartsColumnsWaterfallsHumedadSuelo.updateData(response.data,
                            'columns_stacked_humedad_suelo');
                    }
                })
                .catch(function(error) {
                    console.error('Error loading humedad del suelo stress data:', error);
                    if (error.response) {
                        console.error('Error response:', error.response.data);
                    }
                });
        }

        // Función para cargar las gráficas cuando el componente se monte
        function cargarGraficasHumedadSuelo() {
            console.log('Iniciando cargarGraficasHumedadSuelo...');
            const zona = '{{ $zonaManejo->id ?? '' }}';
            const periodo = '{{ $periodo }}';
            const startDate = '{{ $startDate }}';
            const endDate = '{{ $endDate }}';

            console.log('Parámetros de carga:', {
                zona,
                periodo,
                startDate,
                endDate
            });

            // Cargar gráfica de humedad del suelo
            cargaGraficaHumedadSuelo(zona, periodo, startDate, endDate);

            // Inicializar la gráfica de estrés de humedad del suelo
            if (window.EchartsColumnsWaterfallsHumedadSuelo && window.EchartsColumnsWaterfallsHumedadSuelo.init) {
                console.log('Inicializando gráfica de estrés...');
                window.EchartsColumnsWaterfallsHumedadSuelo.init('columns_stacked_humedad_suelo');
            } else {
                console.error('EchartsColumnsWaterfallsHumedadSuelo no disponible');
            }

            // Cargar datos históricos de estrés de humedad del suelo
            setTimeout(function() {
                cargaGraficaEstresHumedadSuelo(zona, periodo, startDate, endDate);
            }, 500);
        }
        window.cargarGraficasHumedadSuelo = cargarGraficasHumedadSuelo;
        window.cargaGraficaEstresHumedadSuelo = cargaGraficaEstresHumedadSuelo;
    </script>
</div>

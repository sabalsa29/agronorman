<div>
    <!-- Título principal -->
    <div class="text-center mb-4">
        <h1 class="display-4 text-dark font-weight-bold">
            <i class="icon-leaf mr-2"></i>
            Gráfica de Potasio
        </h1>
        <p class="lead text-muted">Monitoreo y análisis del potasio en el suelo</p>
    </div>

    <!-- Gráfica principal de Potasio -->
    <div class="card shadow-lg border-0">
        <div class="card-header header-elements-inline bg-gradient-dark text-white">
            <h3 class="card-title mb-0">
                <i class="icon-graph mr-2"></i>
                Gráfica de Potasio (ppm)
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
                <div class="chart has-fixed-height" id="grafica_potasio"></div>
            </div>
        </div>
    </div>
    <!-- /gráfica principal -->

    <!-- Gráfica de estrés de Potasio -->
    <div class="card shadow-lg border-0 mt-4">
        <div class="card-header header-elements-inline bg-gradient-secondary text-white">
            <div class="d-flex flex-column align-items-start">
                <h3 class="card-title mb-0" id="titulo_estres_potasio">
                    <i class="icon-stats-bars mr-2"></i>
                    Análisis de Estrés por Potasio
                </h3>
                <div id="info_estres_potasio" class="estres-info mt-2" style="font-size: 0.875rem; opacity: 0.9;"></div>
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
                <div class="chart has-fixed-height" id="columns_stacked_potasio"></div>
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

        #columns_stacked_potasio {
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
        console.log('Componente de Potasio cargado....');

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

        function cargaGraficaPotasio(zona, periodo, startDate, endDate) {
            console.log('Intentando cargar gráfica de Potasio', {
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

            console.log('Enviando parámetros para Potasio:', params);

            return axios.get("{{ route('home.grafica_potasio') }}", {
                    params: params
                })
                .then(function(response) {
                    let data = response.data;
                    console.log('Datos de Potasio recibidos:', data);

                    // Actualizar la gráfica de Potasio usando la función existente de areas.js
                    window.EchartsAreas.updateAreaZoom('grafica_potasio', data.labels, data
                        .maximosPotasio, data
                        .minimosPotasio, data
                        .promediosPotasio);

                    // Mensaje visual si no se renderiza nada
                    setTimeout(function() {
                        const grafDiv = document.getElementById('grafica_potasio');
                        if (grafDiv && !grafDiv.innerHTML.trim()) {
                            grafDiv.innerHTML =
                                '<div class="alert alert-warning">No se pudo renderizar la gráfica de Potasio.</div>';
                        }
                    }, 1000);
                })
                .catch(function(error) {
                    console.error('Error loading Potasio chart:', error);
                    const grafDiv = document.getElementById('grafica_potasio');
                    if (grafDiv) {
                        if (error.response) {
                            console.error('Error response:', error.response.data);
                            if (error.response.data && error.response.data.message) {
                                grafDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.response.data
                                    .message + '</div>';
                            } else {
                                grafDiv.innerHTML =
                                    '<div class="alert alert-danger">Error al cargar los datos de Potasio. Código: ' +
                                    error
                                    .response.status + '</div>';
                            }
                        } else {
                            grafDiv.innerHTML =
                                '<div class="alert alert-danger">Error de conexión al cargar los datos de Potasio.</div>';
                        }
                    }
                });
        }

        // Lógica para gráfica de estrés de Potasio
        function cargaGraficaEstresPotasio(zona, periodo, startDate, endDate) {
            if (typeof window.EchartsColumnsWaterfallsPotasio === 'undefined') {
                console.error('EchartsColumnsWaterfallsPotasio no está disponible. Esperando a que se cargue...');
                return;
            }
            const params = {
                estacion_id: zona,
                periodo: periodo,
                startDate: startDate,
                endDate: endDate,
                tipo_cultivo_id: "{{ $tipoCultivoId ?? '' }}",
                etapa_fenologica_id: "{{ $etapaFenologicaId ?? '' }}",
                variable: "pot"
            };
            console.log('Loading Potasio stress data with params:', params);
            return axios.get("{{ route('home.grafica_estres') }}", {
                    params: params
                })
                .then(function(response) {
                    console.log('Potasio stress data response:', response.data);
                    window.EchartsColumnsWaterfallsPotasio.updateData(response.data, 'columns_stacked_potasio');
                })
                .catch(function(error) {
                    console.error('Error loading Potasio stress data:', error);
                    if (error.response) {
                        console.error('Error response:', error.response.data);
                    }
                });
        }

        // Función para cargar las gráficas cuando el componente se monte
        function cargarGraficasPotasio() {
            const zona = '{{ $zonaManejo->id ?? '' }}';
            const periodo = '{{ $periodo }}';
            const startDate = '{{ $startDate }}';
            const endDate = '{{ $endDate }}';
            // Cargar gráfica de Potasio
            cargaGraficaPotasio(zona, periodo, startDate, endDate);
            // Inicializar la gráfica de estrés de Potasio
            if (window.EchartsColumnsWaterfallsPotasio && window.EchartsColumnsWaterfallsPotasio.init) {
                window.EchartsColumnsWaterfallsPotasio.init('columns_stacked_potasio');
            }
            // Cargar datos históricos de estrés de Potasio
            cargaGraficaEstresPotasio(zona, periodo, startDate, endDate);
        }
        window.cargarGraficasPotasio = cargarGraficasPotasio;
        window.cargaGraficaEstresPotasio = cargaGraficaEstresPotasio;
    </script>
</div>

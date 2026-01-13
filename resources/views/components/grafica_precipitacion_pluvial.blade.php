<div>
    <!-- Título principal -->
    <div class="text-center mb-4">
        <h1 class="display-4 text-dark font-weight-bold">
            <i class="icon-weather-rain mr-2"></i>
            Precipitación Pluvial
        </h1>
        <p class="lead text-muted">Monitoreo y análisis de precipitación pluvial</p>
    </div>

    <!-- Gráfica de Precipitación Pluvial Histórica -->
    <div class="card shadow-lg border-0 mb-4">
        <div class="card-header header-elements-inline bg-gradient-dark text-white">
            <h3 class="card-title mb-0">
                <i class="icon-graph mr-2"></i>
                Gráfica de Precipitación Pluvial (mm)
            </h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <div class="chart has-fixed-height" id="grafica_precipitacion_pluvial"></div>
            </div>
        </div>
    </div>

    <!-- Gráfica de Precipitación Pluvial Acumulada -->
    <div class="card shadow-lg border-0 mb-4">
        <div class="card-header header-elements-inline bg-gradient-secondary text-white">
            <h3 class="card-title mb-0">
                <i class="icon-graph mr-2"></i>
                Gráfica de Precipitación Pluvial Acumulada (mm)
            </h3>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <div class="chart has-fixed-height" id="grafica_precipitacion_pluvial_acumulada"></div>
            </div>
        </div>
    </div>

    <!-- Tabla de Precipitación Pluvial con Pronóstico -->
    <div class="card shadow-lg border-0 mb-4">
        <div class="card-header header-elements-inline bg-gradient-info text-white">
            <h3 class="card-title mb-0">
                <i class="icon-table2 mr-2"></i>
                Tabla de Precipitación Pluvial con Pronóstico
            </h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered" id="tabla_precipitacion_pluvial">
                    <thead class="thead-dark">
                        <tr>
                            <th class="text-left">Fecha</th>
                            <th class="text-center">Precipitación (mm)</th>
                            <th class="text-center">Acumulado (mm)</th>
                        </tr>
                    </thead>
                    <tbody id="tabla_precipitacion_pluvial_body">
                        <tr>
                            <td colspan="3" class="text-center text-muted">
                                <i class="icon-spinner4 spinner mr-2"></i>
                                Cargando datos...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
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
        console.log('Componente de precipitación pluvial cargado....');

        // Verificar que los scripts estén disponibles
        function verificarScripts() {
            if (typeof window.EchartsAreas === 'undefined') {
                console.error('EchartsAreas no está disponible. Esperando a que se cargue...');
                return false;
            }
            console.log('Scripts cargados correctamente');
            return true;
        }

        function cargaGraficaPrecipitacionPluvial(zona, periodo, startDate, endDate) {
            console.log('Intentando cargar gráfica de precipitación pluvial', {
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

            console.log('Enviando parámetros para precipitación pluvial:', params);

            return axios.get("{{ route('home.grafica_precipitacion_pluvial') }}", {
                    params: params
                })
                .then(function(response) {
                    let data = response.data;
                    console.log('Datos de precipitación pluvial recibidos:', data);

                    // Actualizar la gráfica de precipitación pluvial usando la función existente de areas.js
                    window.EchartsAreas.updateAreaZoom('grafica_precipitacion_pluvial', data.labels, data
                        .maximosPrecipitacion);

                    // Mensaje visual si no se renderiza nada
                    setTimeout(function() {
                        const grafDiv = document.getElementById('grafica_precipitacion_pluvial');
                        if (grafDiv && !grafDiv.innerHTML.trim()) {
                            grafDiv.innerHTML =
                                '<div class="alert alert-warning">No se pudo renderizar la gráfica de precipitación pluvial.</div>';
                        }
                    }, 1000);
                })
                .catch(function(error) {
                    console.error('Error loading precipitación pluvial chart:', error);
                    const grafDiv = document.getElementById('grafica_precipitacion_pluvial');
                    if (grafDiv) {
                        if (error.response) {
                            console.error('Error response:', error.response.data);
                            if (error.response.data && error.response.data.message) {
                                grafDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.response.data
                                    .message + '</div>';
                            } else {
                                grafDiv.innerHTML =
                                    '<div class="alert alert-danger">Error al cargar los datos de precipitación pluvial. Código: ' +
                                    error
                                    .response.status + '</div>';
                            }
                        } else {
                            grafDiv.innerHTML =
                                '<div class="alert alert-danger">Error de conexión al cargar los datos de precipitación pluvial.</div>';
                        }
                    }
                });
        }

        function cargaGraficaPrecipitacionPluvialAcumulada(zona, periodo, startDate, endDate) {
            console.log('Intentando cargar gráfica de precipitación pluvial acumulada', {
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

            console.log('Enviando parámetros para precipitación pluvial acumulada:', params);

            return axios.get("{{ route('home.grafica_precipitacion_pluvial_acumulada') }}", {
                    params: params
                })
                .then(function(response) {
                    let data = response.data;
                    console.log('Datos de precipitación pluvial acumulada recibidos:', data);

                    // Actualizar la gráfica de precipitación pluvial acumulada usando la función existente de areas.js
                    window.EchartsAreas.updateAreaZoom('grafica_precipitacion_pluvial_acumulada', data.labels, data
                        .acumuladoPrecipitacion, null, null, data.indicesPronostico);

                    // Mensaje visual si no se renderiza nada
                    setTimeout(function() {
                        const grafDiv = document.getElementById('grafica_precipitacion_pluvial_acumulada');
                        if (grafDiv && !grafDiv.innerHTML.trim()) {
                            grafDiv.innerHTML =
                                '<div class="alert alert-warning">No se pudo renderizar la gráfica de precipitación pluvial acumulada.</div>';
                        }
                    }, 1000);
                })
                .catch(function(error) {
                    console.error('Error loading precipitación pluvial acumulada chart:', error);
                    const grafDiv = document.getElementById('grafica_precipitacion_pluvial_acumulada');
                    if (grafDiv) {
                        if (error.response) {
                            console.error('Error response:', error.response.data);
                            if (error.response.data && error.response.data.message) {
                                grafDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.response.data
                                    .message + '</div>';
                            } else {
                                grafDiv.innerHTML =
                                    '<div class="alert alert-danger">Error al cargar los datos de precipitación pluvial acumulada. Código: ' +
                                    error
                                    .response.status + '</div>';
                            }
                        } else {
                            grafDiv.innerHTML =
                                '<div class="alert alert-danger">Error de conexión al cargar los datos de precipitación pluvial acumulada.</div>';
                        }
                    }
                });
        }

        function cargaTablaPrecipitacionPluvial(zona, periodo, startDate, endDate) {
            console.log('Intentando cargar tabla de precipitación pluvial', {
                zona,
                periodo,
                startDate,
                endDate
            });

            const params = {
                estacion_id: zona,
                periodo: periodo,
                startDate: startDate,
                endDate: endDate
            };

            console.log('Enviando parámetros para tabla de precipitación pluvial:', params);

            return axios.get("{{ route('home.tabla_precipitacion_pluvial') }}", {
                    params: params
                })
                .then(function(response) {
                    let data = response.data;
                    console.log('Datos de tabla de precipitación pluvial recibidos:', data);

                    const tbody = document.getElementById('tabla_precipitacion_pluvial_body');
                    if (tbody && data.datos) {
                        let html = '';

                        if (data.datos.length === 0) {
                            html =
                                '<tr><td colspan="3" class="text-center text-muted">No hay datos disponibles</td></tr>';
                        } else {
                            data.datos.forEach(function(dato) {
                                const rowClass = dato.es_pronostico ? 'text-left table-warning' : '';
                                const fechaClass = dato.es_pronostico ? 'font-weight-bold text-primary' : '';

                                html += `
                                    <tr class="${rowClass}">
                                        <td class="text-center ${fechaClass}">${dato.fecha}</td>
                                        <td class="text-center">${dato.precipitacion}</td>
                                        <td class="text-center font-weight-bold">${dato.acumulado}</td>
                                    </tr>
                                `;
                            });
                        }

                        tbody.innerHTML = html;
                    }
                })
                .catch(function(error) {
                    console.error('Error loading tabla de precipitación pluvial:', error);
                    const tbody = document.getElementById('tabla_precipitacion_pluvial_body');
                    if (tbody) {
                        let errorMessage = 'Error al cargar los datos de la tabla';
                        if (error.response && error.response.data && error.response.data.message) {
                            errorMessage = error.response.data.message;
                        }
                        tbody.innerHTML =
                            `<tr><td colspan="3" class="text-center text-danger">${errorMessage}</td></tr>`;
                    }
                });
        }

        // Función para cargar las gráficas cuando el componente se monte
        function cargarGraficasPrecipitacionPluvial() {
            const zona = '{{ $zonaManejo->id ?? '' }}';
            const periodo = '{{ $periodo }}';
            const startDate = '{{ $startDate }}';
            const endDate = '{{ $endDate }}';
            // Cargar gráfica de precipitación pluvial
            cargaGraficaPrecipitacionPluvial(zona, periodo, startDate, endDate);
            // Cargar gráfica de precipitación pluvial acumulada
            cargaGraficaPrecipitacionPluvialAcumulada(zona, periodo, startDate, endDate);
            // Cargar tabla de precipitación pluvial con pronóstico
            cargaTablaPrecipitacionPluvial(zona, periodo, startDate, endDate);
        }

        window.cargarGraficasPrecipitacionPluvial = cargarGraficasPrecipitacionPluvial;
    </script>
</div>

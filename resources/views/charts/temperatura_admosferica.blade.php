<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <!-- Global stylesheets -->
    <link href="{{ url('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <!-- /global stylesheets -->

    <!-- jQuery -->
    <script src="{{ url('global_assets/js/main/jquery.min.js') }}"></script>
    <!-- ECharts -->
    <script src="{{ url('global_assets/js/plugins/visualization/echarts/echarts.min.js') }}"></script>
    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/charts/areas.js', 'resources/js/charts/estres.js'])

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

        #columns_stacked {
            min-height: 500px;
            width: 100%;
        }
    </style>
</head>

<body>

    <!-- Stacked clustered columns -->
    <div class="card">
        <div class="card-header header-elements-inline">
            <h5 class="card-title">Temperatura atmosférica</h5>
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
    <!-- /stacked clustered columns -->

    <!-- Stacked clustered columns -->
    <div class="card">
        <div class="card-header header-elements-inline">
            <h5 class="card-title">Gráfica de estrés de temperatura</h5>
            <div class="header-elements">
                <div class="list-icons">
                    <a class="list-icons-item" data-action="collapse"></a>
                    <a class="list-icons-item" data-action="reload"></a>
                    <a class="list-icons-item" data-action="remove"></a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Leyenda de semáforo nutricional -->
            <div class="my-10 mx-auto text-center w-[90%]" style="display: flex; gap: 20px; align-items: center;">
                <span><span
                        style="display:inline-block;width:18px;height:18px;background:#1976d2;border-radius:3px;margin-right:5px;"></span>
                    Azul: Muy bajo</span>
                <span><span
                        style="display:inline-block;width:18px;height:18px;background:#4fc3f7;border-radius:3px;margin-right:5px;"></span>
                    Celeste: Bajo</span>
                <span><span
                        style="display:inline-block;width:18px;height:18px;background:#4caf50;border-radius:3px;margin-right:5px;"></span>
                    Verde: Óptimo</span>
                <span><span
                        style="display:inline-block;width:18px;height:18px;background:#ffeb3b;border-radius:3px;margin-right:5px;border:1px solid #ccc;"></span>
                    Amarillo: Alto</span>
                <span><span
                        style="display:inline-block;width:18px;height:18px;background:#e53935;border-radius:3px;margin-right:5px;"></span>
                    Rojo: Muy alto</span>
            </div>
            <div class="chart-container">
                <div class="chart has-fixed-height" id="columns_stacked"></div>
            </div>

        </div>
    </div>
    <!-- /stacked clustered columns -->

    <script>
        function cargaGraficaTemperatura(zona, periodo, startDate, endDate) {
            EchartsAreas.init();

            const params = {
                estacion_id: zona,
                periodo: periodo,
                startDate: startDate,
                endDate: endDate
            };

            console.log('Sending parameters:', params);

            return axios.get("{{ route('home.grafica_temperatura') }}", {
                    params: params
                })
                .then(function(response) {
                    let data = response.data;
                    console.log('Response data:', data);
                    EchartsAreas.updateAreaZoom('grafica_temperatura', data.labels, data.maximosTemperatura, data
                        .minimosTemperatura, data
                        .promediosTemperatura);
                })
                .catch(function(error) {
                    console.error('Error loading temperature chart:', error);
                    const errorDiv = document.getElementById('error-message');
                    if (error.response) {
                        console.error('Error response:', error.response.data);
                        if (error.response.data && error.response.data.message) {
                            errorDiv.textContent = 'Error: ' + error.response.data.message;
                        } else {
                            errorDiv.textContent = 'Error al cargar los datos de temperatura. Código: ' + error.response
                                .status;
                        }
                    } else {
                        errorDiv.textContent = 'Error de conexión al cargar los datos de temperatura.';
                    }
                    errorDiv.style.display = 'block';
                });
        }

        function cargaGraficaEstres(zona, periodo, startDate, endDate) {
            const params = {
                estacion_id: zona,
                periodo: periodo,
                startDate: startDate,
                endDate: endDate,
                tipo_cultivo_id: "{{ $tipo_cultivo_id ?? '' }}",
                etapa_fenologica_id: "{{ $etapa_fenologica_id ?? '' }}",
                variable: "temperatura"
            };

            console.log('Loading stress data with params:', params);

            return axios.get("{{ route('home.grafica_estres') }}", {
                    params: params
                })
                .then(function(response) {
                    console.log('Stress data response:', response.data);
                    if (typeof window.EchartsColumnsWaterfalls !== 'undefined') {
                        window.EchartsColumnsWaterfalls.updateData(response.data);
                    } else {
                        console.error('EchartsColumnsWaterfalls no está disponible');
                    }
                })
                .catch(function(error) {
                    console.error('Error loading stress data:', error);
                    if (error.response) {
                        console.error('Error response:', error.response.data);
                    }
                });
        }

        // Cargar las gráficas al iniciar la página
        document.addEventListener('DOMContentLoaded', function() {
            const zona = "{{ $zona_manejo }}";
            const periodo = "{{ $periodo }}";
            const startDate = "{{ $startDate }}";
            const endDate = "{{ $endDate }}";

            // Cargar gráfica de temperatura
            cargaGraficaTemperatura(zona, periodo, startDate, endDate);

            // Inicializar la gráfica de estrés
            if (typeof window.EchartsColumnsWaterfalls !== 'undefined') {
                window.EchartsColumnsWaterfalls.init();
                // Cargar datos reales automáticamente
                cargaGraficaEstres(zona, periodo, startDate, endDate);
            } else {
                console.error('EchartsColumnsWaterfalls no está definido');
            }

        });
    </script>
</body>

</html>

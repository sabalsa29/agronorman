/* ------------------------------------------------------------------------------
 *
 *  # Echarts - Variables Múltiples
 *
 *  Demo JS code for variables múltiples charts
 *
 * ---------------------------------------------------------------------------- */

// Setup module
// ------------------------------

var GraficaVariablesMultiples = function () {

    var charts = {};
    var chartIds = [
        'componente_grafica_variables_multiples'
    ];

    //
    // Setup module components
    //

    // Variables múltiples charts
    var _variablesMultiplesChartExamples = function () {
        if (typeof echarts == 'undefined') {
            console.warn('Warning - echarts.min.js is not loaded.');
            return;
        }

        chartIds.forEach(function (chartId) {
            var el = document.getElementById(chartId);
            if (!el) {
                console.warn('Elemento no encontrado:', chartId);
                return;
            }
            if (typeof echarts === 'undefined') {
                console.warn('ECharts no está disponible');
                return;
            }
            // Ensure container fills parent
            el.style.width = '100%';
            el.style.height = '400px';
            // Initialize and store
            var chart = echarts.init(el);

            // Configuración base con colores vibrantes
            chart.setOption({
                color: ['#FF0000', '#00FF00', '#0000FF'],
                textStyle: { fontFamily: 'Roboto, Arial, Verdana, sans-serif', fontSize: 13 },
                animationDuration: 750,
                grid: { left: 0, right: 40, top: 35, bottom: 60, containLabel: true },
                legend: {
                    data: [],
                    itemHeight: 8,
                    itemGap: 20
                },
                tooltip: {
                    trigger: 'axis',
                    backgroundColor: 'rgba(0,0,0,0.75)',
                    padding: [10, 15],
                    textStyle: { fontSize: 13, fontFamily: 'Roboto, sans-serif' }
                },
                xAxis: [{ type: 'category', boundaryGap: false, axisLabel: { color: '#333' }, axisLine: { lineStyle: { color: '#999' } }, data: [] }],
                yAxis: [{
                    type: 'value', axisLabel: { formatter: '{value} ', color: '#333' },
                    axisLine: { lineStyle: { color: '#999' } },
                    splitLine: { lineStyle: { color: '#eee' } },
                    splitArea: { show: true, areaStyle: { color: ['rgba(250,250,250,0.1)', 'rgba(0,0,0,0.01)'] } }
                }],
                dataZoom: [
                    { type: 'inside', start: 0, end: 100 },
                    {
                        show: true, type: 'slider', start: 0, end: 100, height: 40, bottom: 0,
                        borderColor: '#ccc', fillerColor: 'rgba(0,0,0,0.05)', handleStyle: { color: '#585f63' }
                    }
                ],
                series: []
            });
            charts[chartId] = chart;
            console.log('Gráfica inicializada:', chartId);
        });

        //
        // Resize charts
        //

        // Resize function
        var triggerChartResize = function () {
            chartIds.forEach(function (chartId) {
                var chart = charts[chartId];
                if (chart) chart.resize();
            });
        };

        // On sidebar width change
        $(document).on('click', '.sidebar-control', function () {
            setTimeout(function () {
                triggerChartResize();
            }, 0);
        });

        // Debounced resize on window resize event
        var resizeCharts;
        window.addEventListener('resize', function () {
            clearTimeout(resizeCharts);
            resizeCharts = setTimeout(function () {
                triggerChartResize();
            }, 200);
        });
    };

    // Función para crear una nueva gráfica dinámicamente (mantener para compatibilidad)
    var createChart = function (chartId) {
        var el = document.getElementById(chartId);
        if (!el) {
            console.warn('Elemento no encontrado:', chartId);
            return null;
        }

        if (typeof echarts === 'undefined') {
            console.warn('ECharts no está disponible');
            return null;
        }

        // Ensure container fills parent
        el.style.width = '100%';
        el.style.height = '400px';

        // Initialize and store
        var chart = echarts.init(el);
        charts[chartId] = chart;
        chartIds.push(chartId);

        // Configuración base con colores vibrantes
        chart.setOption({
            color: ['#FF0000', '#00FF00', '#0000FF'],
            textStyle: { fontFamily: 'Roboto, Arial, Verdana, sans-serif', fontSize: 13 },
            animationDuration: 750,
            grid: { left: 0, right: 40, top: 35, bottom: 60, containLabel: true },
            legend: {
                data: [],
                itemHeight: 8,
                itemGap: 20
            },
            tooltip: {
                trigger: 'axis',
                backgroundColor: 'rgba(0,0,0,0.75)',
                padding: [10, 15],
                textStyle: { fontSize: 13, fontFamily: 'Roboto, sans-serif' }
            },
            xAxis: [{ type: 'category', boundaryGap: false, axisLabel: { color: '#333' }, axisLine: { lineStyle: { color: '#999' } }, data: [] }],
            yAxis: [{
                type: 'value', axisLabel: { formatter: '{value} ', color: '#333' },
                axisLine: { lineStyle: { color: '#999' } },
                splitLine: { lineStyle: { color: '#eee' } },
                splitArea: { show: true, areaStyle: { color: ['rgba(250,250,250,0.1)', 'rgba(0,0,0,0.01)'] } }
            }],
            dataZoom: [
                { type: 'inside', start: 0, end: 100 },
                {
                    show: true, type: 'slider', start: 0, end: 100, height: 40, bottom: 0,
                    borderColor: '#ccc', fillerColor: 'rgba(0,0,0,0.05)', handleStyle: { color: '#585f63' }
                }
            ],
            series: []
        });

        console.log('Gráfica creada:', chartId);
        return chart;
    };

    // Función para cargar datos (mantener para compatibilidad)
    var cargarDatos = function (chartId, params) {
        console.log('cargarDatos llamado con:', chartId, params);

        // Mostrar loader
        mostrarLoader(chartId);

        // Construir URL con parámetros
        const baseUrl = window.location.origin;
        const url = new URL('/api/grafica_variables_multiples', baseUrl);

        // Agregar parámetros básicos
        url.searchParams.append('estacion_id', params.estacion_id);
        url.searchParams.append('periodo', params.periodo);

        // Agregar fechas si están definidas
        if (params.startDate) {
            url.searchParams.append('startDate', params.startDate);
        }
        if (params.endDate) {
            url.searchParams.append('endDate', params.endDate);
        }

        // Agregar variables y agrupaciones
        if (Array.isArray(params.variables)) {
            params.variables.forEach(variable => {
                url.searchParams.append('variables[]', variable);
            });
        } else {
            url.searchParams.append('variables[]', params.variables);
        }

        if (Array.isArray(params.agrupaciones)) {
            params.agrupaciones.forEach(agrupacion => {
                url.searchParams.append('agrupaciones[]', agrupacion);
            });
        } else {
            url.searchParams.append('agrupaciones[]', params.agrupaciones);
        }

        // Realizar petición
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin'
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Datos recibidos:', data);
                // Usar la función interna procesarDatosVariables
                procesarDatosVariables(chartId, data, params);
            })
            .catch(error => {
                console.error('Error en fetch:', error);
                mostrarError(chartId, 'Error al cargar los datos: ' + error.message);
            });
    };

    // Función interna para procesar datos (igual que updateVariablesData)
    var procesarDatosVariables = function (chartId, data, params) {
        console.log('procesarDatosVariables llamado con:', chartId, data);
        var chart = charts[chartId];
        if (!chart) {
            console.error('Gráfica no encontrada:', chartId);
            return;
        }

        // Procesar datos para ECharts - TODAS LAS VARIABLES EN UNA SOLA GRÁFICA
        const series = [];
        const legendData = [];

        // Paleta de colores para diferentes variables - Colores vibrantes y altamente contrastantes
        const colores = [
            '#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF',
            '#00FFFF', '#FF8000', '#8000FF', '#00FF80', '#FF0080',
            '#80FF00', '#0080FF', '#FF4000', '#4000FF', '#00FF40',
            '#FF0040', '#40FF00', '#0040FF', '#FF2000', '#2000FF',
            '#00FF20', '#FF0020', '#20FF00', '#0020FF', '#FF6000'
        ];

        // Extraer variables y agrupaciones de los datos
        const variables = Object.keys(data).filter(key => key !== 'labels');

        // Agrupar por variable (no por agrupación)
        const variablesAgrupadas = {};
        variables.forEach(variable => {
            const agrupacion = variable.replace(/^(maxs|mins|avgs)/, '').toLowerCase();
            const tipo = variable.startsWith('maxs') ? 'Máximo' :
                variable.startsWith('mins') ? 'Mínimo' : 'Promedio';

            if (!variablesAgrupadas[agrupacion]) {
                variablesAgrupadas[agrupacion] = {};
            }
            variablesAgrupadas[agrupacion][tipo] = data[variable];
        });

        // Crear series para cada variable con sus agrupaciones
        Object.keys(variablesAgrupadas).forEach((variable, indexVariable) => {
            const nombreVariable = getNombreVariable(variable);
            const colorBase = colores[indexVariable % colores.length];

            Object.keys(variablesAgrupadas[variable]).forEach((tipo, indexTipo) => {
                const nombreSerie = `${nombreVariable} (${tipo})`;
                legendData.push(nombreSerie);

                // Variar el color para cada tipo de agrupación con mejor visibilidad
                let colorSerie;
                if (tipo === 'Máximo') {
                    colorSerie = colorBase; // Color base sólido y vibrante
                } else if (tipo === 'Mínimo') {
                    // Versión más clara pero aún visible
                    colorSerie = colorBase + 'CC'; // Transparencia ligera
                } else { // Promedio
                    // Versión intermedia muy visible
                    colorSerie = colorBase + 'E6'; // Transparencia mínima
                }

                series.push({
                    name: nombreSerie,
                    type: 'line',
                    smooth: true,
                    symbolSize: 6,
                    areaStyle: { opacity: 0.25 },
                    itemStyle: {
                        borderWidth: 2,
                        color: colorSerie
                    },
                    lineStyle: {
                        color: colorSerie
                    },
                    data: variablesAgrupadas[variable][tipo]
                });
            });
        });

        // Actualizar gráfica con todas las series
        chart.setOption({
            title: { text: '' }, // Limpiar el título "Cargando datos..."
            color: colores, // Usar la paleta de colores
            legend: {
                data: legendData,
                itemHeight: 8,
                itemGap: 20,
                type: 'scroll' // Hacer scrollable si hay muchas variables
            },
            xAxis: [{ data: data.labels }],
            series: series
        });
    };

    // Función interna para obtener nombre de variable
    var getNombreVariable = function (variable) {
        const nombres = {
            'temperatura': 'Temperatura (°C)',
            'humedad_relativa': 'Humedad Relativa (%)',
            'radiacion_solar': 'Radiación Solar (W/m²)',
            'precipitacion_acumulada': 'Precipitación (mm)',
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
        return nombres[variable] || variable;
    };

    // Función para mostrar loader
    var mostrarLoader = function (chartId) {
        var chart = charts[chartId];
        if (!chart) {
            console.error('Gráfica no encontrada:', chartId);
            return;
        }

        chart.setOption({
            title: {
                text: 'Cargando datos...',
                left: 'center',
                top: 'center',
                textStyle: {
                    fontSize: 16,
                    color: '#666'
                }
            },
            xAxis: [{ data: [] }],
            series: []
        });
    };

    // Función para mostrar error
    var mostrarError = function (chartId, mensaje) {
        var chart = charts[chartId];
        if (!chart) {
            console.error('Gráfica no encontrada:', chartId);
            return;
        }

        chart.setOption({
            title: {
                text: mensaje,
                left: 'center',
                top: 'center',
                textStyle: {
                    fontSize: 14,
                    color: '#d32f2f'
                }
            },
            xAxis: [{ data: [] }],
            series: []
        });
    };

    // Función para limpiar gráfica
    var limpiar = function (chartId) {
        var chart = charts[chartId];
        if (!chart) {
            console.error('Gráfica no encontrada:', chartId);
            return;
        }

        chart.setOption({
            title: { text: '' },
            xAxis: [{ data: [] }],
            series: []
        });
    };

    //
    // Return objects assigned to module
    //

    return {
        init: function () {
            _variablesMultiplesChartExamples();
        },
        createChart: createChart,
        cargarDatos: cargarDatos,
        limpiar: limpiar,
        mostrarLoader: mostrarLoader,
        mostrarError: mostrarError,
        updateVariablesData: function (chartId, data, params) {
            console.log('updateVariablesData llamado con:', chartId, data);
            var chart = charts[chartId];
            if (!chart) {
                console.error('Gráfica no encontrada:', chartId);
                return;
            }

            // Procesar datos para ECharts - TODAS LAS VARIABLES EN UNA SOLA GRÁFICA
            const series = [];
            const legendData = [];

            // Paleta de colores para diferentes variables - Colores vibrantes y altamente contrastantes
            const colores = [
                '#FF0000', '#00FF00', '#0000FF', '#FFFF00', '#FF00FF',
                '#00FFFF', '#FF8000', '#8000FF', '#00FF80', '#FF0080',
                '#80FF00', '#0080FF', '#FF4000', '#4000FF', '#00FF40',
                '#FF0040', '#40FF00', '#0040FF', '#FF2000', '#2000FF',
                '#00FF20', '#FF0020', '#20FF00', '#0020FF', '#FF6000'
            ];

            // Extraer variables y agrupaciones de los datos
            const variables = Object.keys(data).filter(key => key !== 'labels');

            // Agrupar por variable (no por agrupación)
            const variablesAgrupadas = {};
            variables.forEach(variable => {
                const agrupacion = variable.replace(/^(maxs|mins|avgs)/, '').toLowerCase();
                const tipo = variable.startsWith('maxs') ? 'Máximo' :
                    variable.startsWith('mins') ? 'Mínimo' : 'Promedio';

                if (!variablesAgrupadas[agrupacion]) {
                    variablesAgrupadas[agrupacion] = {};
                }
                variablesAgrupadas[agrupacion][tipo] = data[variable];
            });

            // Crear series para cada variable con sus agrupaciones
            Object.keys(variablesAgrupadas).forEach((variable, indexVariable) => {
                const nombreVariable = getNombreVariable(variable);
                const colorBase = colores[indexVariable % colores.length];

                Object.keys(variablesAgrupadas[variable]).forEach((tipo, indexTipo) => {
                    const nombreSerie = `${nombreVariable} (${tipo})`;
                    legendData.push(nombreSerie);

                    // Variar el color para cada tipo de agrupación con mejor diferenciación
                    let colorSerie;
                    if (tipo === 'Máximo') {
                        colorSerie = colorBase; // Color base sólido
                    } else if (tipo === 'Mínimo') {
                        // Versión más clara con transparencia
                        colorSerie = colorBase + '80'; // Agregar transparencia
                    } else { // Promedio
                        // Versión intermedia con transparencia
                        colorSerie = colorBase + 'CC'; // Transparencia intermedia
                    }

                    series.push({
                        name: nombreSerie,
                        type: 'line',
                        smooth: true,
                        symbolSize: 6,
                        areaStyle: { opacity: 0.25 },
                        itemStyle: {
                            borderWidth: 2,
                            color: colorSerie
                        },
                        lineStyle: {
                            color: colorSerie
                        },
                        data: variablesAgrupadas[variable][tipo]
                    });
                });
            });

            // Actualizar gráfica con todas las series
            chart.setOption({
                title: { text: '' }, // Limpiar el título "Cargando datos..."
                color: colores, // Usar la paleta de colores
                legend: {
                    data: legendData,
                    itemHeight: 8,
                    itemGap: 20,
                    type: 'scroll' // Hacer scrollable si hay muchas variables
                },
                xAxis: [{ data: data.labels }],
                series: series
            });
        },

        // Obtener nombre legible de la variable
        getNombreVariable: function (variable) {
            const nombres = {
                'temperatura': 'Temperatura (°C)',
                'humedad_relativa': 'Humedad Relativa (%)',
                'radiacion_solar': 'Radiación Solar (W/m²)',
                'precipitacion_acumulada': 'Precipitación (mm)',
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
            return nombres[variable] || variable;
        }
    }
}();

// Initialize module
// ------------------------------

document.addEventListener('DOMContentLoaded', function () {
    GraficaVariablesMultiples.init();
});

window.GraficaVariablesMultiples = GraficaVariablesMultiples; 
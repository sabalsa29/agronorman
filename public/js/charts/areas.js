/* ------------------------------------------------------------------------------
 *
 *  # Echarts - Area charts
 *
 *  Demo JS code for echarts_areas.html page
 *
 * ---------------------------------------------------------------------------- */


// Setup module
// ------------------------------

var EchartsAreas = function () {

    var charts = {};
    var chartIds = [
        'grafica_temperatura',
        'grafica_co2',
        'grafica_temperatura_suelo',
        'grafica_humedad_relativa',
        'grafica_humedad_suelo',
        'grafica_precipitacion_pluvial',
        'grafica_precipitacion_pluvial_acumulada',
        'grafica_ph',
        'grafica_nitrogeno',
        'grafica_fosforo',
        'grafica_potasio',
        'grafica_conductividad_electrica',
        'grafica_velocidad_viento',
        'grafica_presion_atmosferica'
    ];

    // Configuración de escalas personalizadas por tipo de gráfica
    var scaleConfig = {
        'grafica_ph': {
            padding: 0.2,  // Menos padding para escala más ajustada
            precision: 2   // Más precisión decimal para pH
        },
        'grafica_conductividad_electrica': {
            padding: 0.5,
            precision: 1
        },
        'grafica_nitrogeno': {
            padding: 0.2,
            precision: 1
        },
        'grafica_fosforo': {
            padding: 0.2,
            precision: 1
        },
        'grafica_potasio': {
            padding: 0.2,
            precision: 1
        },
        'grafica_velocidad_viento': {
            padding: 0.2,
            precision: 1
        },
        'grafica_presion_atmosferica': {
            padding: 0.2,
            precision: 1
        },
        'grafica_humedad_relativa': {
            padding: 0.2,
            precision: 1
        }
    };

    // Función helper para calcular escala personalizada
    function calculateCustomScale(chartId, minValue, maxValue) {
        var config = scaleConfig[chartId];
        if (!config) {
            return { min: minValue, max: maxValue };
        }

        // Lógica especial para pH - escala más detallada
        if (chartId === 'grafica_ph') {
            var range = maxValue - minValue;
            var padding = Math.max(0.1, range * 0.1); // 10% del rango como mínimo
            var min = Math.floor((minValue - padding) * 100) / 100;
            var max = Math.ceil((maxValue + padding) * 100) / 100;

            // Asegurar que la escala sea al menos de 1 unidad para pH
            if (max - min < 1) {
                var center = (min + max) / 2;
                min = Math.floor((center - 0.5) * 100) / 100;
                max = Math.ceil((center + 0.5) * 100) / 100;
            }

            return { min: min, max: max };
        }

        // Para otras gráficas, usar la lógica original
        var factor = Math.pow(10, config.precision);
        var min = Math.floor(minValue * factor) / factor - config.padding;
        var max = Math.ceil(maxValue * factor) / factor + config.padding;

        return { min: min, max: max };
    }

    //
    // Setup module components
    //

    // Area charts
    var _areaChartExamples = function () {
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
            // Determinar las leyendas según el tipo de gráfica
            let legendData = [
                'Máximos Temperatura atmosférica',
                'Mínimos Temperatura atmosférica',
                'Promedios Temperatura atmosférica'
            ];

            if (chartId === 'grafica_co2') {
                legendData = [
                    'Máximos CO₂ atmosférico',
                    'Mínimos CO₂ atmosférico',
                    'Promedios CO₂ atmosférico'
                ];
            } else if (chartId === 'grafica_temperatura_suelo') {
                legendData = [
                    'Máximos Temperatura del suelo',
                    'Mínimos Temperatura del suelo',
                    'Promedios Temperatura del suelo'
                ];
            } else if (chartId === 'grafica_humedad_relativa') {
                legendData = [
                    'Máximos Humedad relativa',
                    'Mínimos Humedad relativa',
                    'Promedios Humedad relativa'
                ];
            } else if (chartId === 'grafica_humedad_suelo') {
                legendData = [
                    'Máximos Humedad del suelo',
                    'Mínimos Humedad del suelo',
                    'Promedios Humedad del suelo'
                ];
            } else if (chartId === 'grafica_precipitacion_pluvial') {
                legendData = [
                    'Precipitación pluvial',
                ];
            } else if (chartId === 'grafica_ph') {
                legendData = [
                    'Máximos pH',
                    'Mínimos pH',
                    'Promedios pH'
                ];
            } else if (chartId === 'grafica_nitrogeno') {
                legendData = [
                    'Máximos Nitrógeno',
                    'Mínimos Nitrógeno',
                    'Promedios Nitrógeno'
                ];
            } else if (chartId === 'grafica_fosforo') {
                legendData = [
                    'Máximos Fósforo',
                    'Mínimos Fósforo',
                    'Promedios Fósforo'
                ];
            } else if (chartId === 'grafica_potasio') {
                legendData = [
                    'Máximos Potasio',
                    'Mínimos Potasio',
                    'Promedios Potasio'
                ];
            } else if (chartId === 'grafica_conductividad_electrica') {
                legendData = [
                    'Máximos Conductividad Eléctrica',
                    'Mínimos Conductividad Eléctrica',
                    'Promedios Conductividad Eléctrica'
                ];
            } else if (chartId === 'grafica_precipitacion_pluvial_acumulada') {
                legendData = [
                    'Precipitación Pluvial Acumulada y Pronóstico'
                ];
            } else if (chartId === 'grafica_velocidad_viento') {
                legendData = [
                    'Máximos Velocidad del Viento',
                    'Mínimos Velocidad del Viento',
                    'Promedios Velocidad del Viento'
                ];
            } else if (chartId === 'grafica_presion_atmosferica') {
                legendData = [
                    'Máximos Presión Atmosférica',
                    'Mínimos Presión Atmosférica',
                    'Promedios Presión Atmosférica'
                ];
            }

            chart.setOption({
                color: ['#3366cc', '#FF0000', '#FFFF00'],
                textStyle: { fontFamily: 'Roboto, Arial, Verdana, sans-serif', fontSize: 16 },
                animationDuration: 750,
                grid: { left: 0, right: 40, top: 35, bottom: 60, containLabel: true },
                legend: {
                    data: legendData,
                    itemHeight: 8,
                    itemGap: 20,
                    textStyle: { fontSize: 16, color: '#333' }
                },
                tooltip: {
                    trigger: 'axis',
                    backgroundColor: 'rgba(0,0,0,0.75)',
                    padding: [10, 15],
                    textStyle: { fontSize: 16, fontFamily: 'Roboto, sans-serif' }
                },
                xAxis: [{
                    type: 'category',
                    boundaryGap: false,
                    axisLabel: { color: '#333', fontSize: 12 },
                    axisLine: { lineStyle: { color: '#999' } },
                    data: []
                }],
                yAxis: [{
                    type: 'value',
                    axisLabel: { formatter: '{value} ', color: '#333', fontSize: 16 },
                    axisLine: { lineStyle: { color: '#999' } },
                    splitLine: { lineStyle: { color: '#eee' } },
                    splitArea: { show: true, areaStyle: { color: ['rgba(250,250,250,0.1)', 'rgba(0,0,0,0.01)'] } }
                    // La escala se ajustará dinámicamente cuando se carguen los datos
                }],
                dataZoom: [
                    { type: 'inside', start: 0, end: 100 },
                    {
                        show: true, type: 'slider', start: 0, end: 100, height: 40, bottom: 0,
                        borderColor: '#ccc', fillerColor: 'rgba(0,0,0,0.05)', handleStyle: { color: '#585f63' }
                    }
                ],
                series: [
                    { name: legendData[0], type: 'line', smooth: true, symbolSize: 6, areaStyle: { opacity: 0.25 }, itemStyle: { borderWidth: 2 }, data: [] },
                    { name: legendData[1], type: 'line', smooth: true, symbolSize: 6, areaStyle: { opacity: 0.25 }, itemStyle: { borderWidth: 2 }, data: [] },
                    { name: legendData[2], type: 'line', smooth: true, symbolSize: 6, areaStyle: { opacity: 0.25 }, itemStyle: { borderWidth: 2 }, data: [] }
                ]
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


    //
    // Return objects assigned to module
    //

    return {
        init: function () {
            _areaChartExamples();
        },
        // Función para actualizar configuración de escalas
        updateScaleConfig: function (chartId, config) {
            if (config && typeof config === 'object') {
                scaleConfig[chartId] = { ...scaleConfig[chartId], ...config };
                console.log('Configuración de escala actualizada para:', chartId, config);
            }
        },
        // Función para obtener configuración actual de escalas
        getScaleConfig: function (chartId) {
            return scaleConfig[chartId] || null;
        },
        updateAreaZoom: function (chartId, labels, maximosTemperatura, minimosTemperatura, promediosTemperatura, indicesPronostico) {
            console.log('updateAreaZoom llamado con:', chartId, labels, maximosTemperatura, minimosTemperatura, promediosTemperatura);
            var chart = charts[chartId];
            if (!chart) {
                console.error('Gráfica no encontrada:', chartId);
                return;
            }

            // Determinar las leyendas según el tipo de gráfica
            let legendData = [
                'Máximos Temperatura atmosférica',
                'Mínimos Temperatura atmosférica',
                'Promedios Temperatura atmosférica'
            ];

            if (chartId === 'grafica_co2') {
                legendData = [
                    'Máximos CO₂ atmosférico',
                    'Mínimos CO₂ atmosférico',
                    'Promedios CO₂ atmosférico'
                ];
            } else if (chartId === 'grafica_temperatura_suelo') {
                legendData = [
                    'Máximos Temperatura del suelo',
                    'Mínimos Temperatura del suelo',
                    'Promedios Temperatura del suelo'
                ];
            } else if (chartId === 'grafica_humedad_relativa') {
                legendData = [
                    'Máximos Humedad relativa',
                    'Mínimos Humedad relativa',
                    'Promedios Humedad relativa'
                ];
            } else if (chartId === 'grafica_humedad_suelo') {
                legendData = [
                    'Máximos Humedad del suelo',
                    'Mínimos Humedad del suelo',
                    'Promedios Humedad del suelo'
                ];
            } else if (chartId === 'grafica_precipitacion_pluvial') {
                legendData = [
                    'Precipitación pluvial'
                ];
            } else if (chartId === 'grafica_precipitacion_pluvial_acumulada') {
                legendData = [
                    'Precipitación Pluvial Acumulada y Pronóstico'
                ];
            } else if (chartId === 'grafica_ph') {
                legendData = [
                    'Máximos pH',
                    'Mínimos pH',
                    'Promedios pH'
                ];
            } else if (chartId === 'grafica_nitrogeno') {
                legendData = [
                    'Máximos Nitrógeno',
                    'Mínimos Nitrógeno',
                    'Promedios Nitrógeno'
                ];
            } else if (chartId === 'grafica_fosforo') {
                legendData = [
                    'Máximos Fósforo',
                    'Mínimos Fósforo',
                    'Promedios Fósforo'
                ];
            } else if (chartId === 'grafica_potasio') {
                legendData = [
                    'Máximos Potasio',
                    'Mínimos Potasio',
                    'Promedios Potasio'
                ];
            } else if (chartId === 'grafica_conductividad_electrica') {
                legendData = [
                    'Máximos Conductividad Eléctrica',
                    'Mínimos Conductividad Eléctrica',
                    'Promedios Conductividad Eléctrica'
                ];
            } else if (chartId === 'grafica_velocidad_viento') {
                legendData = [
                    'Máximos Velocidad del Viento',
                    'Mínimos Velocidad del Viento',
                    'Promedios Velocidad del Viento'
                ];
            } else if (chartId === 'grafica_presion_atmosferica') {
                legendData = [
                    'Máximos Presión Atmosférica',
                    'Mínimos Presión Atmosférica',
                    'Promedios Presión Atmosférica'
                ];
            }

            // Calcular rangos personalizados para la escala (seguro para arrays grandes)
            var allData = [];
            if (Array.isArray(maximosTemperatura) && maximosTemperatura.length) allData = allData.concat(maximosTemperatura);
            if (Array.isArray(minimosTemperatura) && minimosTemperatura.length) allData = allData.concat(minimosTemperatura);
            if (Array.isArray(promediosTemperatura) && promediosTemperatura.length) allData = allData.concat(promediosTemperatura);
            // Filtrar valores no numéricos y convertir a número
            allData = allData
                .filter(function (v) { return v !== null && v !== undefined && v !== '' && !Number.isNaN(Number(v)); })
                .map(function (v) { return Number(v); });
            // Evitar usar spread para no desbordar la pila
            var minValue = Infinity;
            var maxValue = -Infinity;
            for (var i = 0; i < allData.length; i++) {
                var v = allData[i];
                if (v < minValue) minValue = v;
                if (v > maxValue) maxValue = v;
            }
            if (!isFinite(minValue) || !isFinite(maxValue)) { minValue = 0; maxValue = 1; }
            // Añadir un pequeño padding para evitar recortes visuales y valores "pegados" al borde
            var range = maxValue - minValue;
            if (!isFinite(range) || range <= 0) {
                minValue = minValue - 1;
                maxValue = maxValue + 1;
            } else {
                var pad = Math.max(0.5, range * 0.05); // 5% del rango o mínimo 0.5
                minValue = minValue - pad;
                maxValue = maxValue + pad;
            }

            // Usar función helper para calcular escala personalizada
            var scale = calculateCustomScale(chartId, minValue, maxValue);

            // Configurar series según el tipo de gráfica
            var series = [];

            // Para precipitación pluvial, solo usar máximo
            if (chartId === 'grafica_precipitacion_pluvial') {
                series.push({ name: legendData[0], data: maximosTemperatura });
            } else if (chartId === 'grafica_precipitacion_pluvial_acumulada') {
                // Para precipitación acumulada, usar maximosTemperatura como datos acumulados (que vienen como acumuladoPrecipitacion)
                if (maximosTemperatura) {
                    // Configurar la serie con colores especiales para pronóstico
                    var serieConfig = {
                        name: legendData[0],
                        data: maximosTemperatura,
                        type: 'line',
                        smooth: true,
                        symbolSize: 6,
                        areaStyle: { opacity: 0.25 },
                        itemStyle: { borderWidth: 2 }
                    };

                    // Si hay índices de pronóstico, configurar colores especiales
                    if (indicesPronostico && indicesPronostico.length > 0) {
                        serieConfig.itemStyle = {
                            borderWidth: 2,
                            color: function (params) {
                                // Si el índice está en la lista de pronóstico, usar color diferente
                                if (indicesPronostico.includes(params.dataIndex)) {
                                    return '#FF6B35'; // Color naranja para pronóstico
                                }
                                return '#3366cc'; // Color azul para datos históricos
                            }
                        };
                        serieConfig.lineStyle = {
                            color: function (params) {
                                // Para las líneas entre puntos, usar color diferente si es pronóstico
                                if (indicesPronostico.includes(params.dataIndex)) {
                                    return '#FF6B35'; // Color naranja para pronóstico
                                }
                                return '#3366cc'; // Color azul para datos históricos
                            }
                        };
                    }

                    series.push(serieConfig);
                }
            } else {
                // Para otras gráficas, usar las 3 series
                series.push({ name: legendData[0], data: maximosTemperatura });
                if (minimosTemperatura) series.push({ name: legendData[1], data: minimosTemperatura });
                if (promediosTemperatura) series.push({ name: legendData[2], data: promediosTemperatura });
            }

            chart.setOption({
                xAxis: [{ data: labels }],
                yAxis: [{
                    min: scale.min,
                    max: scale.max
                }],
                series: series
            });
        }
    }
}();


// Initialize module
// ------------------------------

document.addEventListener('DOMContentLoaded', function () {
    EchartsAreas.init();
});

window.EchartsAreas = EchartsAreas;

/*
EJEMPLO DE USO PARA ESCALAS PERSONALIZADAS:

// Ajustar escala para pH con más precisión
EchartsAreas.updateScaleConfig('grafica_ph', {
    padding: 0.3,    // Menos padding para escala más ajustada
    precision: 2     // Más precisión decimal
});

// Ajustar escala para conductividad eléctrica
EchartsAreas.updateScaleConfig('grafica_conductividad_electrica', {
    padding: 0.1,    // Muy poco padding
    precision: 1
});

// Ver configuración actual
console.log(EchartsAreas.getScaleConfig('grafica_ph'));
*/
console.log('estres_enfermedades.js cargado y ejecutándose');

var hours = [];
for (var h = 0; h < 24; h++) {
    hours.push(h.toString().padStart(2, '0') + ':00');
}

window.EchartsColumnsWaterfallsEnfermedades = (function () {
    var charts = {}; // Objeto para almacenar múltiples instancias de gráficas

    function updateData(sinRiesgo, bajo, alto, total, enfermedadId, fechas) {
        console.log('updateData', sinRiesgo, bajo, alto, total, enfermedadId, fechas);

        // Verificar si ya existe una instancia para esta enfermedad
        if (!charts[enfermedadId]) {
            var elementId = 'columns_stacked_enfermedad_' + enfermedadId;
            console.log('Buscando elemento con ID:', elementId);

            var el = document.getElementById(elementId);
            if (!el) {
                console.error('No se encontró el elemento columns_stacked_enfermedad_' + enfermedadId);
                console.log('Elementos disponibles con "columns_stacked_enfermedad":');
                var allElements = document.querySelectorAll('[id*="columns_stacked_enfermedad"]');
                allElements.forEach(function (elem) {
                    console.log('- Encontrado:', elem.id);
                });
                return;
            }
            console.log('Elemento encontrado:', el.id);
            charts[enfermedadId] = echarts.init(el);
            console.log('Nueva instancia de gráfica creada para enfermedad:', enfermedadId);
        } else {
            console.log('Usando instancia existente para enfermedad:', enfermedadId);
        }

        // Mostrar loader nativo de ECharts
        charts[enfermedadId].showLoading();

        // Generar etiqueta de rango de fechas basada en los datos recibidos
        var dias = [];
        if (fechas) {
            dias = [fechas];
        } else {
            // Si no hay fechas o están vacías, usar "Día 1" como fallback
            dias = ['Día 1'];
        }

        console.log('Rango de fechas generado para la gráfica:', dias);
        var datosAgrupados = [
            {
                sin_riesgo: sinRiesgo,
                bajo: bajo,
                alto: alto,
                total: total,
            }
        ];

        var cumuloReal = null;

        // Definir las categorías de estrés con colores más vibrantes
        var categorias = [
            { nombre: 'Sin Riesgo', color: '#28a745', key: 'sin_riesgo' }, // Verde
            { nombre: 'Bajo', color: '#ffc107', key: 'bajo' },             // Amarillo
            { nombre: 'Alto', color: '#dc3545', key: 'alto' },             // Rojo
        ];

        // Agrupado por día (igual que pronóstico)
        var series = categorias.map(function (categoria) {
            return {
                name: categoria.nombre,
                type: 'bar',
                stack: 'total',
                barWidth: 100,
                data: datosAgrupados.map(function (dia) {
                    return dia[categoria.key] || 0;
                }),
                itemStyle: { color: categoria.color },
                label: {
                    show: true,
                    position: 'inside',
                    fontSize: 11,
                    color: '#fff',
                    fontWeight: 'bold',
                    verticalAlign: 'middle',
                    formatter: function (params) {
                        var categoria = categorias.find(c => c.nombre === params.seriesName);
                        var key = categoria ? categoria.key : null;
                        var valor = params.value;

                        // Obtener el porcentaje correspondiente
                        var porcentaje = 0;
                        if (key === 'sin_riesgo') {
                            porcentaje = sinRiesgo;
                        } else if (key === 'bajo') {
                            porcentaje = bajo;
                        } else if (key === 'alto') {
                            porcentaje = alto;
                        }

                        // Solo mostrar etiqueta si hay valor
                        if (valor > 0) {
                            return categoria.nombre + ': ' + porcentaje + '%';
                        }
                        return '';
                    }
                }
            };
        });

        var option = {
            tooltip: {
                trigger: 'axis',
                axisPointer: { type: 'shadow' },
                padding: [10, 15],
                formatter: function (params) {
                    let label = params[0].axisValueLabel;
                    let html = `<b>${label}</b><br/>`;

                    // Mostrar los datos específicos de cada categoría
                    html += `<span style='display:inline-block;margin-right:5px;border-radius:10px;width:10px;height:10px;background:#28a745'></span>`;
                    html += `Sin Riesgo: ${sinRiesgo}%<br/>`;

                    html += `<span style='display:inline-block;margin-right:5px;border-radius:10px;width:10px;height:10px;background:#ffc107'></span>`;
                    html += `Bajo: ${bajo}%<br/>`;

                    html += `<span style='display:inline-block;margin-right:5px;border-radius:10px;width:10px;height:10px;background:#dc3545'></span>`;
                    html += `Alto: ${alto}%<br/>`;

                    html += `<hr style='margin:5px 0'/>`;
                    html += `<strong>Total: ${total}</strong>`;

                    return html;
                }
            },
            grid: { left: 0, right: 0, top: 0, bottom: 0, containLabel: true },
            xAxis: { type: 'value', min: 0, max: 100, axisLabel: { color: '#333', fontSize: 10 } },
            yAxis: { type: 'category', data: dias, axisLabel: { color: '#333', fontSize: 10 } },
            legend: {
                data: categorias.map(function (cat) {
                    let porcentaje = '';
                    if (cumuloReal && cumuloReal[cat.key] !== undefined && cumuloReal[cat.key].p !== undefined) {
                        porcentaje = ` (${cumuloReal[cat.key].p}%)`;
                    }
                    return { name: cat.nombre + porcentaje, icon: 'rect' };
                }),
                itemWidth: 18,
                itemHeight: 12,
                itemGap: 18,
                top: 10,
                textStyle: { color: '#333' }
            },
            color: categorias.map(function (cat) { return cat.color; }),
            series: series
        };

        charts[enfermedadId].setOption(option);
        charts[enfermedadId].hideLoading();
    }

    function init(enfermedadId) {
        var el = document.getElementById('columns_stacked_enfermedad_' + enfermedadId);
        if (el) {
            charts[enfermedadId] = echarts.init(el);
            console.log('Gráfica inicializada para enfermedad:', enfermedadId);
        }
    }

    function destroy(enfermedadId) {
        if (charts[enfermedadId]) {
            charts[enfermedadId].dispose();
            delete charts[enfermedadId];
            console.log('Gráfica destruida para enfermedad:', enfermedadId);
        }
    }

    function destroyAll() {
        Object.keys(charts).forEach(function (enfermedadId) {
            destroy(enfermedadId);
        });
        console.log('Todas las gráficas han sido destruidas');
    }

    return {
        init: init,
        updateData: updateData,
        destroy: destroy,
        destroyAll: destroyAll
    };
})(); 
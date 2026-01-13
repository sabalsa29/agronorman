console.log('estres_pronostico_humedad_relativa.js cargado y ejecutándose');

var hours = [];
for (var h = 0; h < 24; h++) {
    hours.push(h.toString().padStart(2, '0') + ':00');
}

window.EchartsColumnsWaterfallsHumedadRelativa = (function () {
    var columns_stacked_humedad = null;

    function updateData(data) {
        if (!columns_stacked_humedad) {
            var el = document.getElementById('columns_stacked_humedad');
            if (!el) {
                console.error('No se encontró el elemento columns_stacked_humedad');
                return;
            }
            columns_stacked_humedad = echarts.init(el);
        }

        // Mostrar loader nativo de ECharts
        columns_stacked_humedad.showLoading();

        // Verificar si tenemos datos agrupados
        if (!data || !data.datos_agrupados || !Array.isArray(data.datos_agrupados)) {
            console.error('No se encontraron datos agrupados');
            columns_stacked_humedad.hideLoading();
            return;
        }

        var dias = data.labels && Array.isArray(data.labels) ? data.labels : ['Día 1'];
        var datosAgrupados = data.datos_agrupados;

        var cumuloReal = (data.cumulo_real && data.cumulo_real.length > 0) ? data.cumulo_real[0] : null;

        // Definir las categorías de estrés con colores más vibrantes
        var categorias = [
            { nombre: 'Muy bajo', color: '#FF0048', key: 'muy_bajo' },
            { nombre: 'Bajo', color: '#FFFF00', key: 'bajo' },
            { nombre: 'Óptimo', color: '#00913F', key: 'optimo' },
            { nombre: 'Alto', color: '#FFFF00', key: 'alto' },
            { nombre: 'Muy alto', color: '#FF0000', key: 'muy_alto' }
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
                    fontSize: 12,
                    color: '#222',
                    verticalAlign: 'middle',
                    formatter: function (params) {
                        var categoria = categorias.find(c => c.nombre === params.seriesName);
                        var key = categoria ? categoria.key : null;
                        var horas = '';
                        var porcentaje = '';
                        if (cumuloReal && key && cumuloReal[key] !== undefined) {
                            horas = cumuloReal[key].h !== undefined ? cumuloReal[key].h : '';
                            porcentaje = cumuloReal[key].p !== undefined ? cumuloReal[key].p + '%' : '';
                        }
                        if (horas) {
                            var texto = horas + ', '
                            if (porcentaje) {
                                texto += porcentaje;
                            }
                            return texto;
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
                    let total = 0;
                    let totalAcumulado = data.total_horas_periodo || total;
                    params.forEach(function (p) {
                        if (p.value > 0) {
                            var categoria = categorias.find(c => c.nombre === p.seriesName);
                            var key = categoria ? categoria.key : null;
                            var horas = '';
                            var porcentaje = '';
                            if (cumuloReal && key && cumuloReal[key] !== undefined) {
                                horas = cumuloReal[key].h !== undefined ? cumuloReal[key].h : '';
                                porcentaje = cumuloReal[key].p !== undefined ? cumuloReal[key].p + '%' : '';
                            }
                            html += `<span style='display:inline-block;margin-right:5px;border-radius:10px;width:10px;height:10px;background:${p.color}'></span>`;
                            html += `${p.seriesName}: `;
                            if (horas) {
                                html += `${horas}h`;
                            }
                            if (porcentaje) {
                                html += ` (${porcentaje})`;
                            }
                            html += `<br/>`;
                            total += p.value;
                        }
                    });
                    html += `<hr style='margin:5px 0'/>`;
                    html += `<strong>Total acumulado: ${totalAcumulado}</strong>`;
                    return html;
                }
            },
            grid: { left: 0, right: 0, top: 0, bottom: 0, containLabel: true },
            xAxis: { type: 'value', min: 0, max: 24, axisLabel: { color: '#333', fontSize: 10 } },
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

        columns_stacked_humedad.setOption(option);
        columns_stacked_humedad.hideLoading();

        // Actualizar título con información del tooltip
        actualizarTituloEstresHumedadRelativa(cumuloReal, data.total_horas_periodo);
    }

    function actualizarTituloEstresHumedadRelativa(cumuloReal, totalAcumulado) {
        var infoElement = document.getElementById('info_estres_humedad_relativa');
        if (!infoElement) {
            console.warn('No se encontró el elemento info_estres_humedad_relativa');
            return;
        }

        var categorias = [
            { nombre: 'Muy bajo', key: 'muy_bajo' },
            { nombre: 'Bajo', key: 'bajo' },
            { nombre: 'Óptimo', key: 'optimo' },
            { nombre: 'Alto', key: 'alto' },
            { nombre: 'Muy alto', key: 'muy_alto' }
        ];

        var partes = [];
        
        if (cumuloReal) {
            categorias.forEach(function(cat) {
                if (cumuloReal[cat.key] !== undefined) {
                    var horas = cumuloReal[cat.key].h !== undefined ? cumuloReal[cat.key].h : '';
                    var porcentaje = cumuloReal[cat.key].p !== undefined ? cumuloReal[cat.key].p : '';
                    if (horas || porcentaje) {
                        var texto = cat.nombre + ': ';
                        if (horas) {
                            texto += horas + 'h';
                        }
                        if (porcentaje) {
                            texto += (horas ? ' ' : '') + '(' + porcentaje + '%)';
                        }
                        partes.push(texto);
                    }
                }
            });
        }

        var textoTotal = '';
        if (totalAcumulado) {
            textoTotal = ' | Total acumulado: ' + totalAcumulado + 'h';
        }

        var infoCompleto = '';
        if (partes.length > 0) {
            infoCompleto = partes.join(', ') + textoTotal;
        } else if (textoTotal) {
            infoCompleto = textoTotal;
        }

        infoElement.textContent = infoCompleto;
    }

    function init() {
        var el = document.getElementById('columns_stacked_humedad');
        if (el) {
            columns_stacked_humedad = echarts.init(el);
        }
    }

    return {
        init: init,
        updateData: updateData
    };
})(); 
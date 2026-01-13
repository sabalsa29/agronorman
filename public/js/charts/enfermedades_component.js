/* ------------------------------------------------------------------------------
 *
 *  # Gauge Chart Only (gauge_custom)
 *
 *  JS code for initializing gauge_custom with dynamic data from Blade
 *
 * ---------------------------------------------------------------------------- */

// Setup module
// ------------------------------
var EnfermedadesComponent = function () {

    // Create a gauge chart inside the specified containerId
    var _createGauge = function (containerId, gaugeValue, gaugeName, gaugeColor) {
        if (typeof echarts == 'undefined') {
            console.warn('Warning - echarts.min.js is not loaded.');
            return;
        }
        // Find the element by provided ID
        var element = document.getElementById(containerId);
        if (!element) {
            console.warn('Gauge container not found:', containerId);
            return;
        }
        // Initialize chart
        var gauge = echarts.init(element);

        // Build options using passed-in data
        var options = {
            textStyle: {
                fontFamily: 'Roboto, Arial, Verdana, sans-serif',
                fontSize: 13
            },
            title: {
                text: gaugeName,
                subtext: '',
                left: 'center',
                bottom: '15%',
                textStyle: {
                    fontSize: 17,
                    fontWeight: 900,
                    color: gaugeColor || '#000',
                }
            },
            tooltip: {
                trigger: 'item',
                backgroundColor: 'rgba(0,0,0,0.75)',
                padding: [10, 15],
                textStyle: {
                    fontSize: 13,
                    fontFamily: 'Roboto, sans-serif'
                },
                formatter: '{a} <br/>{b} : {c}%'
            },
            series: [
                {
                    name: '',
                    type: 'gauge',
                    center: ['50%', '50%'],
                    radius: '100%',
                    startAngle: 180,
                    endAngle: 0,
                    axisLine: {
                        lineStyle: {
                            color: [
                                [0.33, '#00A14B'],    // Verde (0-33%)
                                [0.66, '#FEBC17'],    // Amarillo (33-66%)
                                [1, '#EF4136']        // Rojo (66-100%)
                            ],
                            width: 30
                        }
                    },
                    axisTick: {
                        splitNumber: 5,
                        length: 5,
                        lineStyle: {
                            color: '#fff'
                        }
                    },
                    axisLabel: {
                        formatter: function (v) {
                            switch (v + '') {
                                case '33': return 'Bajo Riesgo';
                                case '66': return 'Riesgo Medio';
                                case '100': return 'Alto Riesgo';
                                default: return '';
                            }
                        }
                    },
                    splitLine: {
                        length: 35,
                        lineStyle: {
                            color: '#fff'
                        }
                    },
                    pointer: {
                        width: 5
                    },
                    detail: {
                        offsetCenter: ['0%', 35],
                        formatter: '{value}%',
                        textStyle: {
                            fontSize: 25,
                            fontWeight: 500
                        }
                    },
                    data: [{ value: gaugeValue, name: '' }]
                }
            ]
        };

        // Set the option on this gauge
        gauge.setOption(options);

        // Handle resize
        var resizeHandler = function () {
            gauge.resize();
        };
        window.addEventListener('resize', resizeHandler);
    };

    return {
        /**
         * Create a gauge chart in the element with ID = containerId
         * @param {string} containerId - DOM id of the container
         * @param {number} value - gauge value (percentage)
         * @param {string} name - gauge title
         * * @param {string} color - gauge title
         */
        create: function (containerId, value, name, color) {
            _createGauge(containerId, value, name, color);
        }
    };
}();

// Expose globally
window.EnfermedadesComponent = EnfermedadesComponent;

// After GaugeCustomFactory definition, initialize gauges for each enfermedad
document.addEventListener('DOMContentLoaded', function () {
    if (!window.enfermedadesIds || !window.enfermedadesData) {
        console.warn('enfermedadesIds or enfermedadesData not defined');
        return;
    }
    window.enfermedadesIds.forEach(function (id) {

        var enfermedad = window.enfermedadesData[id];

        if (!enfermedad) {
            console.warn('No enfermedad data for enfermedad ID', id);
            return;
        }
        var containerId = 'gauge_custom_enfermedad_' + id;
        EnfermedadesComponent.create(
            containerId,
            enfermedad.porcentaje,
            enfermedad.etapa,
            enfermedad.color
        );
    });
});
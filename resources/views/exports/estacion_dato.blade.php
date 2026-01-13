<table>
    <tr>
        <td>
            <table>
                <tr>
                    <td colspan="2"><strong>Usuario:</strong></td>
                    <td colspan="2">{{ $cliente->nombre }}</td>
                </tr>
                <tr>
                    <td colspan="2"><strong>Predio:</strong></td>
                    <td colspan="2">{{ $parcela->nombre }}</td>
                </tr>
                <tr>
                    <td colspan="2"><strong>Zona de manejo:</strong></td>
                    <td colspan="2">{{ $zona_manejo->nombre }}</td>
                </tr>
                <tr>
                    <td colspan="2"><strong>Cultivo:</strong></td>
                    <td colspan="2">{{ $tipo_cultivo[0]->nombre }}</td>
                </tr>
            </table>
        </td>
    </tr>
    <tr>
        @for ($i = 0; $i < 10; $i++)
            <td></td>
        @endfor
        <td colspan="6" align="center"><strong>PLATAFORMA DE INTELIGENCIA AGRONÓMICA</strong></td>
    </tr>
    <tr>
        @for ($i = 0; $i < 17; $i++)
            <td></td>
        @endfor
        <td colspan="4"><strong>Fecha exportación:</strong></td>
        <td colspan="6">{{ $fechaExportacion }}</td>
    </tr>
    <tr>
        @for ($i = 0; $i < 17; $i++)
            <td></td>
        @endfor
        <td colspan="4"><strong>Fecha de última transmisión:</strong></td>
        <td colspan="6">{{ $fechaUltimaTransmision }}</td>
    </tr>
</table>

<br>

<table>
    <thead>
        <tr>
            <th colspan="1"></th>
            <th colspan="15"><strong>Fenología</strong></th>
            <th colspan="15"><strong>Nutrición</strong></th>
            <th colspan="9"><strong>Riego</strong></th>
        </tr>
        <tr>
            <th>Fecha</th>
            <th colspan="3"><strong>Temperatura atmosférica</strong></th>
            <th colspan="3"><strong>CO2 atmosférico</strong></th>
            <th colspan="3"><strong>Temperatura del suelo</strong></th>
            <th colspan="3"><strong>Velocidad del viento</strong></th>
            <th colspan="3"><strong>Presión Atmosférica</strong></th>
            <th colspan="3"><strong>Potencial de hidrógeno</strong></th>
            <th colspan="3"><strong>Nitrógeno</strong></th>
            <th colspan="3"><strong>Fósforo</strong></th>
            <th colspan="3"><strong>Potasio</strong></th>
            <th colspan="3"><strong>Conductividad Eléctrica</strong></th>
            <th colspan="3"><strong>Humedad Relativa Atmosférica</strong></th>
            <th colspan="3"><strong>Humedad del suelo</strong></th>
            <th colspan="3"><strong>Precipitación Pluvial</strong></th>

            <!-- Continúa igual como en el machote -->
        </tr>
        <tr>
            <th></th>
            <th>Max</th>
            <th>Min</th>
            <th>Pro</th>
            <th>Max</th>
            <th>Min</th>
            <th>Pro</th>
            <th>Max</th>
            <th>Min</th>
            <th>Pro</th>
            <th>Max</th>
            <th>Min</th>
            <th>Pro</th>
            <th>Max</th>
            <th>Min</th>
            <th>Pro</th>
            <th>Max</th>
            <th>Min</th>
            <th>Pro</th>
            <th>Max</th>
            <th>Min</th>
            <th>Pro</th>
            <th>Max</th>
            <th>Min</th>
            <th>Pro</th>
            <th>Max</th>
            <th>Min</th>
            <th>Pro</th>
            <th>Max</th>
            <th>Min</th>
            <th>Pro</th>
            <th>Max</th>
            <th>Min</th>
            <th>Pro</th>
            <th>Max</th>
            <th>Min</th>
            <th>Pro</th>
            <!-- Continúa -->
        </tr>
    </thead>
    <tbody>
        @foreach ($datos as $registro)
            <tr>
                <td>{{ $registro['fecha_real'] }}</td>
                <td>{{ number_format($registro['max_temperatura'], 2) }}</td>
                <td>{{ number_format($registro['min_temperatura'], 2) }}</td>
                <td>{{ number_format($registro['avg_temperatura'], 2) }}</td>
                <td>{{ number_format($registro['max_co2'], 2) }}</td>
                <td>{{ number_format($registro['min_co2'], 2) }}</td>
                <td>{{ number_format($registro['avg_co2'], 2) }}</td>
                <td>{{ number_format($registro['max_temperatura_suelo'], 2) }}</td>
                <td>{{ number_format($registro['min_temperatura_suelo'], 2) }}</td>
                <td>{{ number_format($registro['avg_temperatura_suelo'], 2) }}</td>
                <td>{{ number_format($registro['max_wind_speed'], 2) }}</td>
                <td>{{ number_format($registro['min_wind_speed'], 2) }}</td>
                <td>{{ number_format($registro['avg_wind_speed'], 2) }}</td>
                <td>{{ number_format($registro['max_pressure'], 2) }}</td>
                <td>{{ number_format($registro['min_pressure'], 2) }}</td>
                <td>{{ number_format($registro['avg_pressure'], 2) }}</td>
                <td>{{ number_format($registro['max_ph'], 2) }}</td>
                <td>{{ number_format($registro['min_ph'], 2) }}</td>
                <td>{{ number_format($registro['avg_ph'], 2) }}</td>
                <td>{{ number_format($registro['max_nit'], 2) }}</td>
                <td>{{ number_format($registro['min_nit'], 2) }}</td>
                <td>{{ number_format($registro['avg_nit'], 2) }}</td>
                <td>{{ number_format($registro['max_phos'], 2) }}</td>
                <td>{{ number_format($registro['min_phos'], 2) }}</td>
                <td>{{ number_format($registro['avg_phos'], 2) }}</td>
                <td>{{ number_format($registro['max_pot'], 2) }}</td>
                <td>{{ number_format($registro['min_pot'], 2) }}</td>
                <td>{{ number_format($registro['avg_pot'], 2) }}</td>
                <td>{{ number_format($registro['max_conductividad_electrica'], 2) }}</td>
                <td>{{ number_format($registro['min_conductividad_electrica'], 2) }}</td>
                <td>{{ number_format($registro['avg_conductividad_electrica'], 2) }}</td>
                <td>{{ number_format($registro['max_humedad_relativa'], 2) }}</td>
                <td>{{ number_format($registro['min_humedad_relativa'], 2) }}</td>
                <td>{{ number_format($registro['avg_humedad_relativa'], 2) }}</td>
                <td>{{ number_format($registro['max_humedad_15'], 2) }}</td>
                <td>{{ number_format($registro['min_humedad_15'], 2) }}</td>
                <td>{{ number_format($registro['avg_humedad_15'], 2) }}</td>
                <td>{{ number_format($registro['max_precipitacion_mm'], 2) }}</td>
                <td>{{ number_format($registro['min_precipitacion_mm'], 2) }}</td>
                <td>{{ number_format($registro['avg_precipitacion_mm'], 2) }}</td>

            </tr>
        @endforeach
    </tbody>
</table>

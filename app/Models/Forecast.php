<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use App\Models\TipoCultivos;

class Forecast extends Authenticatable
{
    protected $table = 'forecast';

    public $timestamps = true;

    protected $fillable = [
        'parcela_id',
        'fecha_solicita',
        'hora_solicita',
        'lat',
        'lon',
        'fecha_prediccion',
        'summary',
        'icon',
        'uvindex',
        'sunriseTime',
        'sunsetTime',
        'temperatureHigh',
        'temperatureHighTime',
        'temperatureLow',
        'temperatureLowTime',
        'precipProbability',
        'hourly'
    ];

    /**
     * Devuelve el estado de plaga para una fecha y zona dados.
     *
     * @param  string  $date  Fecha de predicción en formato 'Y-m-d'
     * @param  object  $zona  Objeto que tiene propiedades parcela_id y especie_id
     * @return array
     *
     * @throws ModelNotFoundException  si no se encuentra Forecast o Especie
     */
    public function statusPlagueByDate(string $date, $zona, $plaga = null): array
    {
        // 1) Obtener el pronóstico más reciente para la parcela y fecha dada
        $forecast = self::where('parcela_id', $zona->parcela_id)
            ->where('fecha_prediccion', $date)
            ->orderBy('fecha_solicita', 'desc')
            ->first();

        if (! $forecast) {
            throw new ModelNotFoundException("No se encontró pronóstico para parcela {$zona->parcela_id} en fecha {$date}.");
        }

        $tempBaseCalor = $plaga->umbral_min;

        // 2) Calcular temperatura promedio del día
        $tempAvg = ($forecast->temperatureHigh + $forecast->temperatureLow) / 2;

        // 3) Calcular unidades de calor usando la fórmula: Max(0, ((T_max + T_min) / 2 - T_base))
        $unidadesCalor = max(0, $tempAvg - $tempBaseCalor);

        // 4) Traducir el día de la semana al nombre en español
        $carbonDate  = Carbon::parse($date);
        $dayOfWeekEs = $carbonDate->locale('es')->isoFormat('dddd');
        // Por ejemplo: "martes", "miércoles". Si quieres capitalizar la primera letra:
        $dayOfWeekEs = ucfirst($dayOfWeekEs);

        return [
            'dayOfWeek' => $dayOfWeekEs,
            'uc'        => $unidadesCalor,
            'tempAvg'   => $tempAvg
        ];
    }


    /**
     * Para un UC inicial y una lista de fechas, calcula UC acumuladas con rebalse según temp_base_calor.
     *
     * @param  float  $ucInicial    UC acumuladas hasta hoy.
     * @param  array  $fechas       Arreglo de fechas en formato 'Y-m-d' a pronosticar.
     * @param  object $zona         Objeto que contiene parcela_id y temp_base_calor.
     * @param  object $tipoCultivo  Modelo TipoCultivo asociado (opcional).
     * @return array                Vector asociativo: fecha => ['ucRestante','generacionesDia','dayOfWeek'].
     *
     * @throws ModelNotFoundException Si no se encuentra un pronóstico para alguna fecha.
     */
    public function forecastWithRollover(float $ucInicial, array $fechas, $zona, $tipoCultivo = null): array
    {
        $resultado    = [];
        $ucAcumulado  = $ucInicial;

        // Determinar umbral dinámico desde temp_base_calor del tipoCultivo o de la zona
        if ($tipoCultivo && isset($tipoCultivo->cultivo->temp_base_calor)) {
            $umbral = $tipoCultivo->cultivo->temp_base_calor;
        } else {
            $umbral = $zona->temp_base_calor;
        }

        foreach ($fechas as $fecha) {
            // Obtener UC pronosticadas para la fecha (sin acumular)
            $datosParaDia = $this->statusPlagueByDate($fecha, $zona, $tipoCultivo);
            $ucDiario     = $datosParaDia['uc'] ?? 0.0;

            // Sumar al acumulado previo
            $suma = $ucAcumulado + $ucDiario;

            // Cuántas generaciones completas se rebasan hoy
            $veces = (int) floor($suma / $umbral);

            // Calcular el resto que queda para mañana
            $ucRestante = round($suma - ($veces * $umbral), 2);

            // Guardar en el arreglo resultado
            $resultado[$fecha] = [
                'uc'      => $ucRestante,
                'generacionesDia' => $veces,
                'dayOfWeek'       => $datosParaDia['dayOfWeek'],
                'umbral'     => $umbral,
            ];

            // El acumulado para el siguiente día parte del resto
            $ucAcumulado = $ucRestante;
        }

        return $resultado;
    }
}

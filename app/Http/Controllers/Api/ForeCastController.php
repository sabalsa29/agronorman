<?php

namespace App\Http\Controllers\Api;

use App\Models\Forecast;
use App\Models\ForecastHourly;
use App\Models\Parcelas;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class ForeCastController extends Controller
{

    // Datos de pronostico
    public function guardaPronostico()
    {
        Log::info('Iniciando guardaPronostico');

        // Verificar que la API key esté configurada
        $apiKey = config('services.openweathermap.key');
        $baseUrl = config('services.openweathermap.base_url');
        $timezone = config('services.openweathermap.timezone');

        if (empty($apiKey)) {
            Log::error('API key de OpenWeatherMap no configurada');
            return response()->json([
                'errors' => ['API key de OpenWeatherMap no configurada'],
                'status' => 500
            ], 500);
        }

        $parcelas = Parcelas::all();
        Log::info('Total de parcelas encontradas: ' . $parcelas->count());

        if ($parcelas->isEmpty()) {
            Log::error('No se encontraron parcelas en la base de datos');
            return response()->json([
                'errors' => ["No se encontraron parcelas"],
                'status' => 500
            ], 500);
        }

        $parcelasConDatos = 0;
        $errores = [];
        $client = new \GuzzleHttp\Client;

        foreach ($parcelas as $parcela) {
            Log::info("Procesando parcela ID: {$parcela->id}");

            if (is_null($parcela->lat) || is_null($parcela->lon)) {
                Log::warning("Parcela {$parcela->id} no tiene coordenadas");
                $errores[] = "Parcela {$parcela->id} no tiene coordenadas";
                continue;
            }

            try {
                $hourlyApiUrl = "{$baseUrl}/onecall?lat={$parcela->lat}&lon={$parcela->lon}&appid={$apiKey}&units=metric&tz={$timezone}";

                Log::info("Consultando API para parcela {$parcela->id}");

                $hourlyResponse = $client->request('GET', $hourlyApiUrl);

                if ($hourlyResponse->getStatusCode() != 200) {
                    $errorMsg = "Error en API para parcela {$parcela->id}: HTTP " . $hourlyResponse->getStatusCode();
                    Log::error($errorMsg);
                    $errores[] = $errorMsg;
                    continue;
                }

                $data = json_decode($hourlyResponse->getBody());

                if (!$this->validarRespuestaAPI($data)) {
                    $errorMsg = "Datos incompletos o inválidos de API para parcela {$parcela->id}";
                    Log::error($errorMsg);
                    $errores[] = $errorMsg;
                    continue;
                }

                DB::beginTransaction();

                $fecha_prediccion = date('Y-m-d');
                $hora_solicita = date('H:00');
                $forecast = [];
                $hours = [];

                // Procesar datos diarios
                foreach ($data->daily as $prediction) {
                    $fecha = date('Y-m-d', $prediction->dt);
                    $forecast[$fecha] = [
                        'hours' => [],
                        'sunriseTime' => date('Y-m-d H:i:s', $prediction->sunrise),
                        'sunsetTime' => date('Y-m-d H:i:s', $prediction->sunset),
                        'temperatureHigh' => $prediction->temp->max,
                        'temperatureLow' => $prediction->temp->min,
                        'precipProbability' => $prediction->pop * 100,
                        'uvindex' => $prediction->uvi ?? null,
                        'summary' => $prediction->summary ?? null,
                        'icon' => $prediction->weather[0]->icon ?? null,
                    ];
                }

                // Procesar datos horarios
                foreach ($data->hourly as $hour) {
                    $fecha = date('Y-m-d', $hour->dt);
                    $hours[$fecha][] = [
                        'hour' => date('Y-m-d H:i:s', $hour->dt),
                        'summary' => $hour->weather[0]->description,
                        'precipProbability' => $hour->pop * 100,
                        'temperature' => $hour->temp,
                        'apparentTemperature' => $hour->feels_like,
                        'humidity' => $hour->humidity,
                        'precipType' => null,
                        'icon' => $hour->weather[0]->icon ?? null,
                    ];

                    $forecast[$fecha]['hours'] = $hours[$fecha];
                }

                $registrosGuardados = $this->guardarPronosticos($forecast, $parcela, $fecha_prediccion, $hora_solicita);

                DB::commit();
                Log::info("Parcela {$parcela->id}: Se guardaron {$registrosGuardados} registros");
                $parcelasConDatos++;
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                DB::rollBack();
                $errorMsg = "Error de conexión para parcela {$parcela->id}: " . $e->getMessage();
                Log::error($errorMsg);
                $errores[] = $errorMsg;
            } catch (\Exception $e) {
                DB::rollBack();
                $errorMsg = "Error procesando parcela {$parcela->id}: " . $e->getMessage();
                Log::error($errorMsg);
                Log::debug($e->getTraceAsString());
                $errores[] = $errorMsg;
            }
        }

        Log::info("Proceso completado. Parcelas procesadas: {$parcelasConDatos}");

        // Determinar el código de respuesta apropiado
        $statusCode = !empty($errores) && $parcelasConDatos === 0 ? 500 : 200;

        $response = [
            'data' => 'forecast',
            'status' => $statusCode,
            'parcelas_procesadas' => $parcelasConDatos,
            'total_parcelas' => $parcelas->count()
        ];

        if (!empty($errores)) {
            $response['warnings'] = $errores;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Valida que la respuesta de la API contenga los datos necesarios
     */
    private function validarRespuestaAPI($data)
    {
        if (!isset($data->daily) || !isset($data->hourly)) {
            return false;
        }

        if (empty($data->daily) || empty($data->hourly)) {
            return false;
        }

        // Validar estructura básica de los datos diarios
        foreach ($data->daily as $prediction) {
            if (!isset(
                $prediction->dt,
                $prediction->sunrise,
                $prediction->sunset,
                $prediction->temp->max,
                $prediction->temp->min,
                $prediction->pop
            )) {
                return false;
            }
        }

        // Validar estructura básica de los datos horarios
        foreach ($data->hourly as $hour) {
            if (!isset(
                $hour->dt,
                $hour->weather[0]->description,
                $hour->pop,
                $hour->temp,
                $hour->feels_like,
                $hour->humidity
            )) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calcula los tiempos de temperatura máxima y mínima basándose en los datos horarios
     */
    private function calcularTiemposTemperatura($hours, $tempHigh, $tempLow)
    {
        $temperatureHighTime = null;
        $temperatureLowTime = null;
        $closestHigh = null;
        $closestLow = null;
        $closestHighTime = null;
        $closestLowTime = null;

        foreach ($hours as $hour) {
            $temp = $hour['temperature'];
            $time = $hour['hour'];

            // Buscar temperatura máxima exacta
            if (abs($temp - $tempHigh) < 0.1) {
                $temperatureHighTime = $time;
            }

            // Buscar temperatura mínima exacta
            if (abs($temp - $tempLow) < 0.1) {
                $temperatureLowTime = $time;
            }

            // Si no encontramos valores exactos, guardar los más cercanos
            if ($closestHigh === null || $temp > $closestHigh) {
                $closestHigh = $temp;
                $closestHighTime = $time;
            }

            if ($closestLow === null || $temp < $closestLow) {
                $closestLow = $temp;
                $closestLowTime = $time;
            }
        }

        // Si no encontramos valores exactos, usar los más cercanos
        if ($temperatureHighTime === null) {
            $temperatureHighTime = $closestHighTime;
        }

        if ($temperatureLowTime === null) {
            $temperatureLowTime = $closestLowTime;
        }

        return [
            'temperatureHighTime' => $temperatureHighTime,
            'temperatureLowTime' => $temperatureLowTime
        ];
    }

    /**
     * Guarda los pronósticos en la base de datos
     */
    private function guardarPronosticos($forecast, $parcela, $fecha_prediccion, $hora_solicita)
    {
        $registrosGuardados = 0;

        foreach ($forecast as $fecha => $pronostico) {
            // Verificar si ya existe un pronóstico para esta parcela y fecha
            $existingForecast = Forecast::where('parcela_id', $parcela->id)
                ->where('fecha_prediccion', $fecha)
                ->where('fecha_solicita', $fecha_prediccion)
                ->first();

            if ($existingForecast) {
                Log::debug("Pronóstico ya existe para parcela {$parcela->id}, fecha {$fecha}");
                continue;
            }

            // Calcular tiempos de temperatura máxima y mínima
            $tiemposTemperatura = $this->calcularTiemposTemperatura(
                $pronostico['hours'],
                $pronostico['temperatureHigh'],
                $pronostico['temperatureLow']
            );

            $data_forecast = new Forecast();
            $data_forecast->parcela_id = $parcela->id;
            $data_forecast->fecha_solicita = $fecha_prediccion;
            $data_forecast->hora_solicita = $hora_solicita;
            $data_forecast->lat = $parcela->lat;
            $data_forecast->lon = $parcela->lon;
            $data_forecast->fecha_prediccion = $fecha;
            $data_forecast->summary = $pronostico['summary'] ?? null;
            $data_forecast->icon = $pronostico['icon'] ?? 'unknown';
            $data_forecast->uvindex = $pronostico['uvindex'] ?? null;
            $data_forecast->sunriseTime = $pronostico['sunriseTime'];
            $data_forecast->sunsetTime = $pronostico['sunsetTime'];
            $data_forecast->temperatureHigh = $pronostico['temperatureHigh'];
            $data_forecast->temperatureHighTime = $tiemposTemperatura['temperatureHighTime'];
            $data_forecast->temperatureLow = $pronostico['temperatureLow'];
            $data_forecast->temperatureLowTime = $tiemposTemperatura['temperatureLowTime'];
            $data_forecast->precipProbability = $pronostico['precipProbability'];
            $data_forecast->hourly = json_encode(array_values($pronostico['hours']));
            $data_forecast->save();
            $registrosGuardados++;

            // Guardar datos horarios
            foreach ($pronostico['hours'] as $hour) {
                $data_forecast_hourly = new ForecastHourly();
                $data_forecast_hourly->forecast_id = $data_forecast->id;
                $data_forecast_hourly->parcela_id = $parcela->id;
                $data_forecast_hourly->fecha = $hour['hour'];
                $data_forecast_hourly->humedad = $hour['humidity'];
                $data_forecast_hourly->temperatura = $hour['temperature'];
                $data_forecast_hourly->save();
            }
        }

        return $registrosGuardados;
    }

    public function pronostico($id)
    {
        //Obtenemos la predicción actual del clima
        $client = new \GuzzleHttp\Client;
        $parcela = Parcelas::find($id);
        $response =  $client->request('GET', "https://api.darksky.net/forecast/0bac6878654fcd301e92a9305b888750/$parcela->lat, $parcela->lon?lang=es&units=si&extend=hourly");
        $pronostico = json_decode($response->getBody());
        //Solicitar el pronóstico del día actual más 6 días
        $startRange = Carbon::today();
        $endRange = Carbon::today()->addDays(6);
        $data = Forecast::where('parcela_id', $id)
            ->where('fecha_prediccion', '>=', $startRange)
            ->where('fecha_prediccion', '<=', $endRange)
            ->where('fecha_solicita', '=', date('Y-m-d'))
            ->orderBy('fecha_prediccion', 'ASC')
            ->get();
        if (count($data) <= 0) {
            return response()->json(['errors' => ["No existen datos para parcela o fecha"], 'status' => 500], 500);
        } else {
            $response = array();
            $i = 0;
            foreach ($data as $dia) {
                if ($i == 0)
                    $title = 'HOY';
                else if ($i == 1)
                    $title = 'MAÑANA';
                else {
                    switch ((int)date('w', strtotime($dia->fecha_prediccion))) {
                        case 0:
                            $title = "DOMINGO";
                            break;
                        case 1:
                            $title = "LUNES";
                            break;
                        case 2:
                            $title = "MARTES";
                            break;
                        case 3:
                            $title = "MIÉRCOLES";
                            break;
                        case 4:
                            $title = "JUEVES";
                            break;
                        case 5:
                            $title = "VIERNES";
                            break;
                        case 6:
                            $title = "SÁBADO";
                            break;
                    }
                }

                $response['dia'][] = array(
                    'title' => $title,
                    'date' => $this->getFechaLetra($dia->fecha_prediccion),
                    'image' => $this->getIconId($dia->icon),
                    'min' => '' . floor($dia->temperatureLow) . '',
                    'max' => '' . floor($dia->temperatureHigh) . ''
                );

                if ($i == 0 || $i == 1) {
                    $response['header'] = array(
                        'estatus' => $pronostico->currently->summary,
                        'uv' => $pronostico->currently->uvIndex,
                        'fps' => '35+',
                        'lluvia' => sprintf("%0.2f", ($pronostico->currently->precipProbability * 100)),
                        'humedad' => floor($pronostico->currently->humidity * 100),
                        'temperatura' => floor($pronostico->currently->temperature),
                        'viento' => $pronostico->currently->windSpeed,
                        'image' => $this->getIconId($pronostico->currently->icon)
                    );
                    $starttimestamp = strtotime($dia->sunriseTime);
                    $endtimestamp = strtotime($dia->sunsetTime);
                    $horaActual = strtotime(date('Y-m-d H:i:s'));
                    $duracion = abs($endtimestamp - $starttimestamp) / 3600;
                    $diurnaRestante = abs($endtimestamp - $horaActual) / 3600;
                    $response['luz'] = array(
                        'duracionDia' => sprintf("%0.2f", $duracion),
                        'salidaSol' => $this->getHour($dia->sunriseTime),
                        'primeraLuz' => '',
                        'mediodia' => '',
                        'luzDiurna' => sprintf("%0.2f", $diurnaRestante),
                        'puestaSol' => $this->getHour($dia->sunsetTime),
                        'ultimaLuz' => '-'
                    );
                    $hourly = json_decode($dia->hourly);
                    foreach ($hourly as $hour) {
                        $response['precipitacion'][] = array(
                            'value' => floor($hour->precipProbability * 100),
                            'time' => $this->getHour($hour->hour)
                        );
                        $response['temperatura'][] = array(
                            'value' => floor($hour->temperature),
                            'time' => $this->getHour($hour->hour),
                            'image' => $this->getIconId($hour->icon)
                        );
                    }
                }

                $i++;
            }
        }

        /*echo "<pre>";
            print_r($response);*/



        return response()->json(['data' => $response, 'status' => 200], 300);
    }

    public function getIconId($icon)
    {
        switch ($icon) {
            case 'partly-cloudy-day':
                $icon = '0';
                break;
            case 'partly-cloudy-night':
                $icon = '0';
                break;
            case 'cloudy':
                $icon = 1;
                break;
            case 'thunderstorm':
                $icon = 3;
                break;
            case 'rain':
                $icon = 4;
                break;
            case 'thunderstorm':
                $icon = 6;
                break;
            case 'clear-day':
                $icon = 7;
                break;
            case 'clear-night':
                $icon = 7;
                break;
            case 'wind':
                $icon = 8;
                break;
        }

        return $icon;
    }

    public function getFechaLetra($fecha_prediccion)
    {
        $fecha = date('d', strtotime($fecha_prediccion));
        switch ((int)date('m', strtotime($fecha_prediccion))) {
            case 1:
                $fecha .= " enero";
                break;
            case 2:
                $fecha .= " febrero";
                break;
            case 3:
                $fecha .= " marzo";
                break;
            case 4:
                $fecha .= " abril";
                break;
            case 5:
                $fecha .= " mayo";
                break;
            case 6:
                $fecha .= " junio";
                break;
            case 7:
                $fecha .= " julio";
                break;
            case 8:
                $fecha .= " agosto";
                break;
            case 9:
                $fecha .= " septiembre";
                break;
            case 10:
                $fecha .= " octubre";
                break;
            case 11:
                $fecha .= " noviembre";
                break;
            case 12:
                $fecha .= " diciembre";
                break;
        }

        return $fecha;
    }

    public function getHour($fecha)
    {
        $fecha = explode(" ", $fecha);
        if (strlen($fecha[1]) == 8) {
            $fecha[1] = substr($fecha[1], 0, -3);
        }
        return $fecha[1];
    }
}

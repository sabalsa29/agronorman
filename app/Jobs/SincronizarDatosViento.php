<?php

namespace App\Jobs;

use App\Models\Parcelas;
use App\Models\DatosViento;
use App\Models\ZonaManejos;
use App\Models\ParcelaErrorViento;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SincronizarDatosViento implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('DEBUG: Entrando a handle() de SincronizarDatosViento');
        try {
            // Obtener parcelas que tienen coordenadas y NO están en la tabla de errores
            $parcelasConError = ParcelaErrorViento::activas()->pluck('parcela_id')->toArray();

            $parcelas = Parcelas::whereNotNull('lat')
                ->whereNotNull('lon')
                ->where('lat', '!=', 0)
                ->where('lon', '!=', 0)
                ->whereNotIn('id', $parcelasConError)
                ->get();

            Log::info('DEBUG: Parcelas encontradas: ' . $parcelas->count() . ' (excluyendo ' . count($parcelasConError) . ' con error)');

            $parcelasExitosas = 0;
            $parcelasConErrorNuevas = [];

            foreach ($parcelas as $parcela) {
                try {
                    Log::info('DEBUG: Procesando parcela con ID: ' . $parcela->id);
                    $this->procesarParcela($parcela);
                    $parcelasExitosas++;
                } catch (\Exception $e) {
                    $errorMsg = "Error procesando parcela {$parcela->nombre} (ID: {$parcela->id}): " . $e->getMessage();
                    Log::error('DEBUG: ' . $errorMsg);

                    // Guardar el error en la tabla
                    $this->guardarErrorParcela($parcela, $e->getMessage());

                    $parcelasConErrorNuevas[] = [
                        'id' => $parcela->id,
                        'nombre' => $parcela->nombre,
                        'error' => $e->getMessage()
                    ];
                    // Continuar con la siguiente parcela
                }
            }

            Log::info("DEBUG: Sincronización completada. Exitosas: {$parcelasExitosas}, Nuevas con error: " . count($parcelasConErrorNuevas));

            if (!empty($parcelasConErrorNuevas)) {
                Log::error('DEBUG: Nuevas parcelas con error: ' . json_encode($parcelasConErrorNuevas));
            }
        } catch (\Exception $e) {
            Log::error('DEBUG: Error general en handle() de SincronizarDatosViento: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Procesar una parcela específica
     */
    private function procesarParcela(Parcelas $parcela): void
    {
        try {
            Log::info("DEBUG: Procesando parcela: {$parcela->nombre} (ID: {$parcela->id})");

            // Obtener zona de manejo asociada a esta parcela
            $zonaManejo = ZonaManejos::where('parcela_id', $parcela->id)->first();
            if (!$zonaManejo) {
                Log::info("DEBUG: No se encontró zona de manejo para parcela {$parcela->id}");
                return;
            }

            // Calcular fechas: solo el día actual
            $ahora = Carbon::now('America/Mexico_City');
            $fechaActual = $ahora->format('Y-m-d');

            // Obtener datos actuales de OpenWeather
            $datosActuales = $this->obtenerDatosActuales($parcela);
            Log::info('DEBUG: Datos actuales obtenidos para parcela ' . $parcela->id . ', velocidad viento: ' . ($datosActuales['wind_speed'] ?? 0));

            // Obtener datos de pronóstico (próximos 3 días)
            $datosPronostico = $this->obtenerDatosPronostico($parcela);
            Log::info('DEBUG: Datos pronóstico obtenidos para parcela ' . $parcela->id . ', registros: ' . count($datosPronostico));

            // Guardar datos actuales (histórico)
            $this->guardarDatos($parcela, $zonaManejo, [$datosActuales], 'historico');

            // Guardar datos de pronóstico
            $this->guardarDatos($parcela, $zonaManejo, $datosPronostico, 'pronostico');

            Log::info("DEBUG: Parcela {$parcela->nombre} procesada exitosamente");
        } catch (\Exception $e) {
            Log::error("DEBUG: Error procesando parcela {$parcela->nombre}: " . $e->getMessage());
        }
    }

    /**
     * Obtener datos actuales de OpenWeather
     */
    private function obtenerDatosActuales(Parcelas $parcela): array
    {
        $apiKey = config('services.openweathermap.key');
        if (!$apiKey) {
            Log::error('API key de OpenWeather no configurada');
            return [];
        }

        $url = "https://api.openweathermap.org/data/2.5/weather";

        $response = Http::get($url, [
            'lat' => $parcela->lat,
            'lon' => $parcela->lon,
            'appid' => $apiKey,
            'units' => 'metric'
        ]);

        if (!$response->successful()) {
            Log::error("Error obteniendo datos actuales para parcela {$parcela->id}: " . $response->status());
            return [];
        }

        $data = $response->json();
        $ahora = Carbon::now('America/Mexico_City');

        // Extraer datos de viento
        $windData = $data['wind'] ?? [];
        $windSpeed = $windData['speed'] ?? 0;
        $windDeg = $windData['deg'] ?? null;
        $windGust = $windData['gust'] ?? null;

        // Convertir dirección de grados a cardinal
        $windDirection = $this->convertirGradosACardinal($windDeg);

        return [
            'fecha_hora_dato' => $ahora->format('Y-m-d H:i:s'),
            'wind_speed' => $windSpeed,
            'wind_gust' => $windGust,
            'wind_deg' => $windDeg,
            'wind_direction' => $windDirection,
            'wind_speed_2m' => null, // No disponible en weather API
            'wind_speed_10m' => $windSpeed, // Usar wind_speed como aproximación
            'wind_gust_10m' => $windGust,
            'datos_raw' => json_encode([
                'wind' => $windData,
                'temp' => $data['main']['temp'] ?? 0,
                'humidity' => $data['main']['humidity'] ?? 0,
                'pressure' => $data['main']['pressure'] ?? 0
            ])
        ];
    }

    /**
     * Obtener datos de pronóstico de OpenWeather
     */
    private function obtenerDatosPronostico(Parcelas $parcela): array
    {
        $apiKey = config('services.openweathermap.key');
        if (!$apiKey) {
            Log::error('API key de OpenWeather no configurada');
            return [];
        }

        $url = "https://api.openweathermap.org/data/2.5/forecast";

        $response = Http::get($url, [
            'lat' => $parcela->lat,
            'lon' => $parcela->lon,
            'appid' => $apiKey,
            'units' => 'metric'
        ]);

        if (!$response->successful()) {
            Log::error("Error obteniendo pronóstico para parcela {$parcela->id}: " . $response->status());
            return [];
        }

        $data = $response->json();
        $datos = [];

        if (isset($data['list'])) {
            // Solo tomar los primeros 3 días (24 registros de 3 horas cada uno = 72 horas)
            $registrosLimitados = array_slice($data['list'], 0, 24);

            foreach ($registrosLimitados as $item) {
                $fechaHora = Carbon::createFromTimestamp($item['dt'], 'America/Mexico_City');

                // Extraer datos de viento
                $windData = $item['wind'] ?? [];
                $windSpeed = $windData['speed'] ?? 0;
                $windDeg = $windData['deg'] ?? null;
                $windGust = $windData['gust'] ?? null;

                // Convertir dirección de grados a cardinal
                $windDirection = $this->convertirGradosACardinal($windDeg);

                $datos[] = [
                    'fecha_hora_dato' => $fechaHora->format('Y-m-d H:i:s'),
                    'wind_speed' => $windSpeed,
                    'wind_gust' => $windGust,
                    'wind_deg' => $windDeg,
                    'wind_direction' => $windDirection,
                    'wind_speed_2m' => null, // No disponible en forecast API
                    'wind_speed_10m' => $windSpeed, // Usar wind_speed como aproximación
                    'wind_gust_10m' => $windGust,
                    'datos_raw' => json_encode([
                        'wind' => $windData,
                        'temp' => $item['main']['temp'] ?? 0,
                        'humidity' => $item['main']['humidity'] ?? 0,
                        'pressure' => $item['main']['pressure'] ?? 0
                    ])
                ];
            }
        }

        return $datos;
    }

    /**
     * Convertir grados a dirección cardinal
     */
    private function convertirGradosACardinal(?int $grados): ?string
    {
        if ($grados === null) {
            return null;
        }

        $direcciones = [
            'N',
            'NNE',
            'NE',
            'ENE',
            'E',
            'ESE',
            'SE',
            'SSE',
            'S',
            'SSW',
            'SW',
            'WSW',
            'W',
            'WNW',
            'NW',
            'NNW'
        ];

        $indice = round($grados / 22.5) % 16;
        return $direcciones[$indice];
    }

    /**
     * Guardar datos en la base de datos
     */
    private function guardarDatos(Parcelas $parcela, ZonaManejos $zonaManejo, array $datos, string $tipoDato): int
    {
        $ahora = Carbon::now('America/Mexico_City');
        $registrosGuardados = 0;

        foreach ($datos as $dato) {
            // Verificar si ya existe un registro para esta fecha/hora
            $existe = DatosViento::where('parcela_id', $parcela->id)
                ->where('fecha_hora_dato', $dato['fecha_hora_dato'])
                ->where('tipo_dato', $tipoDato)
                ->exists();

            if (!$existe) {
                DatosViento::create([
                    'parcela_id' => $parcela->id,
                    'zona_manejo_id' => $zonaManejo->id,
                    'fecha_solicita' => $ahora->format('Y-m-d'),
                    'hora_solicita' => $ahora->format('H:i:s'),
                    'lat' => $parcela->lat,
                    'lon' => $parcela->lon,
                    'fecha_hora_dato' => $dato['fecha_hora_dato'],
                    'wind_speed' => $dato['wind_speed'],
                    'wind_gust' => $dato['wind_gust'],
                    'wind_deg' => $dato['wind_deg'],
                    'wind_direction' => $dato['wind_direction'],
                    'wind_speed_2m' => $dato['wind_speed_2m'],
                    'wind_speed_10m' => $dato['wind_speed_10m'],
                    'wind_gust_10m' => $dato['wind_gust_10m'],
                    'tipo_dato' => $tipoDato,
                    'fuente' => 'openweather',
                    'datos_raw' => $dato['datos_raw']
                ]);

                $registrosGuardados++;
            }
        }

        Log::info("DEBUG: Guardados {$registrosGuardados} registros de {$tipoDato} para parcela {$parcela->nombre}");
        return $registrosGuardados;
    }

    /**
     * Guardar error de parcela en la tabla de errores
     */
    private function guardarErrorParcela(Parcelas $parcela, string $errorMensaje): void
    {
        try {
            // Verificar si ya existe un registro de error para esta parcela
            $errorExistente = ParcelaErrorViento::where('parcela_id', $parcela->id)->first();

            if ($errorExistente) {
                // Actualizar registro existente
                $errorExistente->update([
                    'error_mensaje' => $errorMensaje,
                    'ultimo_intento' => now(),
                    'activo' => true
                ]);
                $errorExistente->incrementarIntento();
            } else {
                // Crear nuevo registro
                ParcelaErrorViento::create([
                    'parcela_id' => $parcela->id,
                    'error_tipo' => 'api_error',
                    'error_mensaje' => $errorMensaje,
                    'intentos_fallidos' => 1,
                    'ultimo_intento' => now(),
                    'activo' => true
                ]);
            }

            Log::info("DEBUG: Error guardado para parcela {$parcela->nombre} (ID: {$parcela->id})");
        } catch (\Exception $e) {
            Log::error("DEBUG: Error al guardar error de parcela {$parcela->id}: " . $e->getMessage());
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job SincronizarDatosViento falló: ' . $exception->getMessage());
    }
}

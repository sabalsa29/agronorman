<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Measurement;

class MqttIngestCommand extends Command
{
    protected $signature = 'mqtt:ingest';
    protected $description = 'Se suscribe a pap/# y guarda mediciones en DB';

    public function handle(): int
    {
        $host = env('MQTT_HOST', '127.0.0.1');
        $port = (int) env('MQTT_PORT', 1883);
        $clientId = env('MQTT_CLIENT_ID', 'pia_ingestor_' . Str::random(6));
        $username = env('MQTT_USERNAME');
        $password = env('MQTT_PASSWORD');
        $topic = env('MQTT_TOPIC', 'pap/#');
        $qos = (int) env('MQTT_QOS', 1);

        $this->info("🚀 Iniciando MQTT ingestor...");
        $this->info("📍 Host: {$host}:{$port}");
        $this->info("📡 Topic: {$topic}");
        $this->info("👤 Username: {$username}");

        try {
            while (true) {
                try {
                    $settings = (new ConnectionSettings)
                        ->setUsername($username)
                        ->setPassword($password)
                        ->setKeepAliveInterval(60)
                        ->setLastWillTopic('system/pia_ingestor/status')
                        ->setLastWillMessage('offline')
                        ->setLastWillQualityOfService(0);

                    $client = new MqttClient($host, $port, $clientId);
                    $client->connect($settings, true);
                    $client->publish('system/pia_ingestor/status', 'online', 0, true);

                    $this->info("✅ Conectado a MQTT {$host}:{$port} | topic {$topic} | qos {$qos}");
                    $this->info("🔍 DEBUG: Topic configurado: '{$topic}'");
                    Log::info('MQTT conectado exitosamente', [
                        'host' => $host,
                        'port' => $port,
                        'topic' => $topic,
                        'qos' => $qos
                    ]);

                    $client->subscribe($topic, function (string $topic, string $message, bool $retained) {
                        $this->info("📨 Mensaje recibido en topic: {$topic}");
                        $this->info("📄 Contenido del mensaje: {$message}");
                        $this->info("🔍 DEBUG: Callback ejecutado correctamente");

                        try {
                            $payload = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
                        } catch (\Throwable $e) {
                            Log::warning('MQTT payload inválido', ['topic' => $topic, 'message' => $message]);
                            return;
                        }

                        // Parseo IMEI desde payload o del topic data/{IMEI}
                        $imei = $payload['estacion_id'] ?? null;
                        if (!$imei && preg_match('#^data/(\d+)#', $topic, $m)) {
                            $imei = $m[1];
                        }
                        if (!$imei) {
                            Log::warning('MQTT sin IMEI', ['topic' => $topic, 'payload' => $payload]);
                            return;
                        }

                        $this->info("📊 Procesando datos para IMEI: {$imei}");

                        // Transformaciones según doc:
                        $dto = [
                            'imei'            => (string) $imei,
                            'transaction_id'  => isset($payload['transaccion_id']) ? (int)$payload['transaccion_id'] : null,

                            'temp_npk_c'      => self::div($payload, 'temp_npk_lv1', 10),
                            'hum_npk_pct'     => self::div($payload, 'hum_npk_lv1', 10),
                            'ph_npk'          => self::div($payload, 'ph_npk_lv1', 100),
                            'cond_us_cm'      => self::get($payload, 'cond_npk_lv1'),
                            'nit_mg_kg'       => self::get($payload, 'nit_npk_lv1'),
                            'pot_mg_kg'       => self::get($payload, 'pot_npk_lv1'),
                            'phos_mg_kg'      => self::get($payload, 'phos_npk_lv1'),

                            'temp_sns_c'      => self::div($payload, 'temp_sns_lv1', 100),
                            'hum_sns_pct'     => self::div($payload, 'hum_sns_lv1', 100),
                            'co2_ppm'         => self::div($payload, 'co2_sns_lv1', 100),

                            'voltaje_mv'      => self::get($payload, 'voltaje'),
                            'contador_mnsj'   => self::get($payload, 'contador_mnsj'),
                            'tec'             => self::get($payload, 'tec'),
                            'ARS'             => (string)($payload['ARS'] ?? ''),
                            'TON'             => self::get($payload, 'TON'),
                            'CELLID'          => (string)($payload['CELLID'] ?? ''),
                            'CIT'             => self::get($payload, 'CIT'),
                            'SWV'             => self::get($payload, 'SWV'),
                            'MNC'             => (string)($payload['MNC'] ?? ''),
                            'MCC'             => (string)($payload['MCC'] ?? ''),
                            'RAT'             => (string)($payload['RAT'] ?? ''),
                            'LAC'             => (string)($payload['LAC'] ?? ''),
                            'PROJECT'         => (string)($payload['PROJECT'] ?? ''),
                            'RSRP'            => self::get($payload, 'RSRP'),
                            'RSRQ'            => self::get($payload, 'RSRQ'),
                            'raw_payload'     => json_encode($payload),
                        ];

                        // Fecha "AA/MM/DD,HH:MM:SS±ZZ" (ZZ en cuartos de hora)
                        $dto['measured_at_utc'] = self::parse1NCETime($payload['fecha'] ?? null);

                        try {
                            Measurement::create($dto);
                            $this->info("✅ Datos guardados para IMEI: {$imei}");
                            $this->info("🌡️  Temp NPK: " . ($dto['temp_npk_c'] ?? 'N/A') . "°C, Temp SNS: " . ($dto['temp_sns_c'] ?? 'N/A') . "°C");
                            $this->info("💨 CO2: " . ($dto['co2_ppm'] ?? 'N/A') . " ppm");
                        } catch (\Throwable $e) {
                            Log::error('Error guardando measurement', ['error' => $e->getMessage(), 'dto' => $dto]);
                            $this->error("❌ Error guardando datos: " . $e->getMessage());
                        }
                    }, $qos);

                    // Loop principal con manejo de errores
                    while (true) {
                        try {
                            $client->loop(false, true, 1);
                            usleep(100000); // 100ms
                        } catch (\Throwable $e) {
                            $this->error("❌ Error en loop MQTT: " . $e->getMessage());
                            break;
                        }
                    }
                } catch (\Throwable $e) {
                    $this->error("❌ Error de conexión MQTT: " . $e->getMessage());
                    Log::error('MQTT connection error', ['err' => $e->getMessage()]);
                    $this->info("🔄 Reintentando conexión en 5 segundos...");
                    sleep(5);
                }
            }
        } catch (\Throwable $e) {
            $this->error("❌ Error fatal: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private static function div(array $a, string $k, int|float $d): ?float
    {
        return isset($a[$k]) ? ((float)$a[$k]) / $d : null;
    }

    private static function get(array $a, string $k): mixed
    {
        return $a[$k] ?? null;
    }

    private static function parse1NCETime(?string $s): ?Carbon
    {
        if (!$s) return null;
        // Ej: "25/09/09,05:51:58-24" => 20(25)/09/09 05:51:58, offset=-24/4=-6h
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{2}),(\d{2}):(\d{2}):(\d{2})([-+]\d{2})$/', $s, $m)) {
            [$all, $yy, $mm, $dd, $H, $i, $sec, $q] = $m;
            $year = 2000 + (int)$yy;
            $dt = Carbon::create($year, (int)$mm, (int)$dd, (int)$H, (int)$i, (int)$sec, 'UTC');
            $offsetHours = (int)$q / 4; // -24 => -6h
            return $dt->addHours($offsetHours);
        }
        return null;
    }
}

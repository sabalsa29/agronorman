<?php

namespace App\Services\Icp;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class IcpClient
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;
    private int $connectTimeout;
    private int $retries;
    private int $retrySleepMs;
    private bool $cacheEnabled;
    private int $cacheTtl;
    private array $endpoints;
    public function __construct()
    {
        $this->baseUrl = config('icp.base_url');
        $this->apiKey = (string) config('icp.api_key');

        $this->timeout = (int) config('icp.timeout', 10);
        $this->connectTimeout = (int) config('icp.connect_timeout', 3);
        $this->retries = (int) config('icp.retries', 2);
        $this->retrySleepMs = (int) config('icp.retry_sleep_ms', 200);

        $this->cacheEnabled = (bool) config('icp.cache.enabled', true);
        $this->cacheTtl = (int) config('icp.cache.ttl', 60);

        $this->endpoints = (array) config('icp.endpoints', []);

        if (empty($this->baseUrl)) {
            throw new \RuntimeException('ICP_API_BASE_URL no está configurado.');
        }
        if (empty($this->apiKey)) {
            throw new \RuntimeException('ICP_API_KEY no está configurado.');
        }
    }

    /**
     * ============================================================
     * Métodos públicos solicitados
     * ============================================================
     */

    public function get_resultados_lote(int|string $lote_id): array
    {
        $path = $this->resolveEndpoint('resultados_lote', ['lote_id' => $lote_id]);
        return $this->getJson($path, cacheKey: "resultados_lote:$lote_id", ttl: 30);
    }

    public function get_indicadores(): array
    {
        $path = $this->resolveEndpoint('indicadores');
        return $this->getJson($path, cacheKey: "indicadores", ttl: 300);
    }

    public function get_secciones(): array
    {
        $path = $this->resolveEndpoint('secciones');
        return $this->getJson($path, cacheKey: "secciones", ttl: 300);
    }

    public function get_elementos(): array
    {
        $path = $this->resolveEndpoint('elementos');
        return $this->getJson($path, cacheKey: "elementos", ttl: 300);
    }

    public function get_cultivos(): array
    {
        $path = $this->resolveEndpoint('cultivos');
        return $this->getJson($path, cacheKey: "cultivos", ttl: 300);
    }

    public function lectura_fechas_correctivos(int|string $lote_id): array
    {
        $path = $this->resolveEndpoint('fechas_correctivos', ['lote_id' => $lote_id]);
        return $this->getJson($path, cacheKey: "fechas_correctivos:$lote_id", ttl: 60);
    }

    public function lectura_correctivos_por_fecha(int|string $lote_id, string $fecha): array
    {
        // fecha ideal en formato ISO (YYYY-MM-DD) o ISO datetime, según tu ICP API
        $path = $this->resolveEndpoint('correctivos_por_fecha', ['lote_id' => $lote_id, 'fecha' => $fecha]);
        return $this->getJson($path, cacheKey: "correctivos_por_fecha:$lote_id:$fecha", ttl: 30);
    }

    /**
     * ============================================================
     * Base HTTP + helpers
     * ============================================================
     */

    private function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->retry($this->retries, $this->retrySleepMs, function ($exception) {
                // Reintentar ante errores de conexión / timeouts.
                return true;
            })
            ->withHeaders([
                'X-API-Key' => $this->apiKey,
                // Si usas Bearer token en ICP, cambia a:
                // 'Authorization' => 'Bearer '.$this->apiKey,
            ]);
    }

    private function getJson(string $path, ?string $cacheKey = null, ?int $ttl = null): array
    {
        $ttl = $ttl ?? $this->cacheTtl;

        if ($this->cacheEnabled && $cacheKey) {
            $fullKey = $this->cachePrefix($cacheKey);

            return Cache::remember($fullKey, $ttl, function () use ($path) {
                return $this->doGetJson($path);
            });
        }

        return $this->doGetJson($path);
    }

    private function doGetJson(string $path): array
    {
        $response = $this->http()->get($path);

        // Lanza excepción si ICP responde 4xx/5xx:
        $response->throw();

        $json = $response->json();

        // Normaliza a array
        if (is_null($json)) {
            return [];
        }

        // Si tu ICP responde { data: [...] } puedes descomentar:
        // return $json['data'] ?? [];

        return is_array($json) ? $json : ['value' => $json];
    }

    private function resolveEndpoint(string $key, array $params = []): string
    {
        if (!isset($this->endpoints[$key])) {
            throw new \InvalidArgumentException("Endpoint ICP no configurado: $key");
        }

        $path = $this->endpoints[$key];

        // Reemplaza {param}
        foreach ($params as $k => $v) {
            $path = str_replace('{'.$k.'}', urlencode((string) $v), $path);
        }

        // Reemplaza query style ?fecha={fecha}
        // (si quedó sin reemplazar, intenta:
        foreach ($params as $k => $v) {
            $path = str_replace('{'.$k.'}', urlencode((string) $v), $path);
        }

        return $path;
    }

    private function cachePrefix(string $key): string
    {
        // Evita keys gigantes y colisiones
        return 'icp:' . Str::slug($key, '_');
    }
}

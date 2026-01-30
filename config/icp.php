<?php

return [
    'base_url' => rtrim(env('ICP_API_BASE_URL', ''), '/'),
    'api_key' => env('ICP_API_KEY', ''),

    'timeout' => (int) env('ICP_HTTP_TIMEOUT', 10),
    'connect_timeout' => (int) env('ICP_HTTP_CONNECT_TIMEOUT', 3),
    'retries' => (int) env('ICP_HTTP_RETRIES', 2),
    'retry_sleep_ms' => (int) env('ICP_HTTP_RETRY_SLEEP_MS', 200),

    'cache' => [
        'enabled' => filter_var(env('ICP_CACHE_ENABLED', true), FILTER_VALIDATE_BOOL),
        'ttl' => (int) env('ICP_CACHE_TTL_SECONDS', 60),
    ],

    // Rutas (ajusta cuando definas endpoints reales en ICP)
    'endpoints' => [
        'resultados_lote' => '/api/v1/lotes/{lote_id}/resultados',
        'indicadores' => '/api/v1/indicadores',
        'secciones' => '/api/v1/secciones',
        'elementos' => '/api/v1/elementos',
        'cultivos' => '/api/v1/cultivos',
        'fechas_correctivos' => '/api/v1/lotes/{lote_id}/correctivos/fechas',
        'correctivos_por_fecha' => '/api/v1/lotes/{lote_id}/correctivos?fecha={fecha}',
    ],
];

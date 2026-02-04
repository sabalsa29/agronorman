<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NutricionEtapaFenologicaTipoCultivoApiController;
use App\Http\Controllers\Api\StationController;
use App\Http\Controllers\AtmosfericosEtapaFenologicaTipoCultivoApiController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InteraccionFactoresController;
use App\Models\VariablesMedicion;
use App\Http\Controllers\Api\AppBitacoraAuthController;
use App\Http\Controllers\Api\AppBitacoraAccesosController;
use App\Http\Controllers\Api\AppBitacoraActividadController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rutas de Estaciones
Route::prefix('estaciones')->group(function () {
    Route::get('/datos', [StationController::class, 'datosWebService']);
    Route::get('/alertas/enfermedades', [StationController::class, 'alertaEnfermedades']);
    Route::get('/alertas/enfermedades/detalle', [StationController::class, 'detalleAlertaEnfermedades']);
    Route::get('/alertas/transmision', [StationController::class, 'alertaTransmision']);
    Route::get('/alertas/plagas', [StationController::class, 'alertaPlagas']);
    Route::post('/s4iot', [StationController::class, 'guardarS4iot']);
    Route::post('/sigfox', [StationController::class, 'guardarSigfox']);
    Route::any('/udp-payload', [StationController::class, 'procesarUdpPayload']);
    Route::get('/udp-datos/{estacion_id}', [StationController::class, 'obtenerDatosUdp']);
    // Debug de estacion_dato con el mismo parser de grafica_estres
    Route::get('/debug/estacion-dato', [HomeController::class, 'debugEstacionDato']);
});

// Rutas de Nutrición Etapa Fenológica Tipo Cultivo
Route::apiResource(
    'nutricion-etapa-fenologica-tipo-cultivo',
    NutricionEtapaFenologicaTipoCultivoApiController::class
)->parameters([
    'nutricion-etapa-fenologica-tipo-cultivo' => 'parametro'
]);

Route::get('nutricion-etapa-fenologica-tipo-cultivo/buscar', [NutricionEtapaFenologicaTipoCultivoApiController::class, 'buscar']);

// Endpoint para gráfica de estrés (genérico)
Route::get('grafica_estres', [HomeController::class, 'grafica_estres'])->name('api.grafica_estres');

// Endpoint para gráfica de variables múltiples
Route::get('grafica_variables_multiples', [HomeController::class, 'grafica_variables_multiples'])->name('api.grafica_variables_multiples');

// Endpoint de prueba para verificar variables disponibles
Route::get('variables-disponibles', function () {
    $columnasValidas = [
        'temperatura',
        'humedad_relativa',
        'radiacion_solar',
        'precipitacion_acumulada',
        'velocidad_viento',
        'direccion_viento',
        'co2',
        'ph',
        'phos',
        'nit',
        'pot',
        'temperatura_suelo',
        'conductividad_electrica',
        'potencial_de_hidrogeno',
        'viento',
        'humedad_15',
        'temperatura_lvl1'
    ];

    return response()->json([
        'variables_disponibles' => $columnasValidas,
        'total_variables' => count($columnasValidas)
    ]);
})->name('variables-disponibles');

// Endpoint de prueba para grafica_variables_multiples
Route::get('test-grafica-variables', [HomeController::class, 'test_grafica_variables_multiples'])->name('api.test_grafica_variables');

// Endpoint para variables de medición
Route::get('variables-medicion', function () {
    try {
        $variables = VariablesMedicion::all();
        return response()->json(['data' => $variables]);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Error al cargar variables de medición', 'message' => $e->getMessage()], 500);
    }
})->name('variables-medicion');

// Endpoint para análisis de interacción de factores
Route::post('interaccion-factores', [InteraccionFactoresController::class, 'analizarInteraccion'])->name('interaccion-factores');

// Endpoint para probar pronósticos de enfermedades
Route::get('test-pronostico-enfermedades', [HomeController::class, 'testPronosticoEnfermedades'])->name('api.test-pronostico-enfermedades');

// Endpoint para probar datos reales de enfermedades
Route::get('test-datos-reales-enfermedades', [HomeController::class, 'testDatosRealesEnfermedades'])->name('api.test-datos-reales-enfermedades');

// Endpoint de login para App Bitácora

Route::prefix('v1/app-bitacora')->group(function () {

    // Login (sin token)
    Route::post('/login', [AppBitacoraAuthController::class, 'login'])
        ->middleware('throttle:10,1');

    // Protegidas con token
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/accesos', [AppBitacoraAccesosController::class, 'index']);
        Route::post('/logout', [AppBitacoraAuthController::class, 'logout']);
        Route::get('/me', [AppBitacoraAuthController::class, 'me']);
        Route::post('/zonas/{id_zm}/actividades', [AppBitacoraActividadController::class, 'store']);
        Route::get('/actividades/{id}', [AppBitacoraActividadController::class, 'show']);
        Route::post('/actividades/{id}', [AppBitacoraActividadController::class, 'update']);

    });
});
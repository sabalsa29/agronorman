<?php

use App\Http\Controllers\AlmacenController;
use App\Http\Controllers\Api\ForeCastController;
use App\Http\Controllers\ClientesController;
use App\Http\Controllers\CultivoController;
use App\Http\Controllers\EnfermedadesController;
use App\Http\Controllers\EstacionDatoExport;
use App\Http\Controllers\EstacionesController;
use App\Http\Controllers\EtapaFenologicaController;
use App\Http\Controllers\EtapaFenologicaTipoCultivoController;
use App\Http\Controllers\FabricanteController;
use App\Http\Controllers\GruposController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ParcelaGrupoController;
use App\Http\Controllers\ParcelasController;
use App\Http\Controllers\PlagaController;
use App\Http\Controllers\TipoCultivosController;
use App\Http\Controllers\TipoEstacionController;
use App\Http\Controllers\TipoSueloController;
use App\Http\Controllers\UserSettingsController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\ZonaManejosController;
use App\Http\Controllers\ConfiguracionMqttController;
use App\Http\Controllers\ConfiguracionMqttUsuarioController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserGrupoController;
use App\Http\Controllers\ZonaGrupoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas de configuración MQTT (autenticación especial)
Route::get('configuracion-mqtt/login', [ConfiguracionMqttController::class, 'showLogin'])->name('configuracion-mqtt.login');
Route::post('configuracion-mqtt/login', [ConfiguracionMqttController::class, 'login']);

Route::middleware([\App\Http\Middleware\ConfiguracionMqttAuth::class])->group(function () {
    Route::get('configuracion-mqtt', [ConfiguracionMqttController::class, 'index'])->name('configuracion-mqtt.index');
    Route::post('configuracion-mqtt/enviar', [ConfiguracionMqttController::class, 'enviarConfiguracion'])->name('configuracion-mqtt.enviar');
    Route::get('configuracion-mqtt/logout', [ConfiguracionMqttController::class, 'logout'])->name('configuracion-mqtt.logout');
    Route::get('configuracion-mqtt/logs', [ConfiguracionMqttController::class, 'logs'])->name('configuracion-mqtt.logs');

    // Rutas de gestión de usuarios MQTT
    Route::resource('configuracion-mqtt/usuarios', ConfiguracionMqttUsuarioController::class)->names([
        'index' => 'configuracion-mqtt.usuarios.index',
        'create' => 'configuracion-mqtt.usuarios.create',
        'store' => 'configuracion-mqtt.usuarios.store',
        'edit' => 'configuracion-mqtt.usuarios.edit',
        'update' => 'configuracion-mqtt.usuarios.update',
        'destroy' => 'configuracion-mqtt.usuarios.destroy',
    ]);
});



Route::get('dashboard', [HomeController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::get('enfermedades_horas', [EnfermedadesController::class, 'jsonEnfermedades']);
Route::get('/api/enfermedades/tipo-cultivo/{tipoCultivoId}/datos', [HomeController::class, 'apiEnfermedadesTipoCultivoDatos'])->name('api.enfermedades.tipo_cultivo.datos');


Route::middleware('auth')->group(function () {
    Route::resource('etapasfenologicas', EtapaFenologicaController::class);
    Route::resource('almacenes', AlmacenController::class);
    Route::resource('fabricantes', FabricanteController::class);
    Route::resource('tipo_estacion', TipoEstacionController::class);
    Route::resource('plaga', PlagaController::class);
    Route::resource('cultivos', CultivoController::class);

    // Dashboard de grupos
    Route::get('grupos/dashboard', [GruposController::class, 'dashboard'])->name('grupos.dashboard');
    // Zonas de manejo simplificadas
    Route::get('grupos/zonas-manejo', [GruposController::class, 'zonasManejo'])->name('grupos.zonas-manejo');
    // Logs de la plataforma (solo super admin)
    Route::get('platform-logs', [\App\Http\Controllers\PlatformLogController::class, 'index'])->name('platform-logs.index');
    Route::get('platform-logs/{platformLog}', [\App\Http\Controllers\PlatformLogController::class, 'show'])->name('platform-logs.show');
    Route::prefix('cultivos/{id}/')->group(function () {
        Route::resource('tipo_cultivos', TipoCultivosController::class);
        Route::prefix('tipo_cultivos/{tipo_cultivo}/')->group(function () {
            Route::resource('parametros',    EtapaFenologicaTipoCultivoController::class);
        });
    });
    Route::resource('grupos', GruposController::class);
    // Enfermedades
    Route::resource('enfermedades', EnfermedadesController::class);
    Route::get('enfermedades/{enfermedad}/cultivos',                    [EnfermedadesController::class, 'cultivosIndex'])->name('enfermedades.cultivos.index');
    Route::get('enfermedades/{enfermedad}/cultivos/create',             [EnfermedadesController::class, 'cultivosCreate'])->name('enfermedades.cultivos.create');
    Route::post('enfermedades/cultivos/store',                          [EnfermedadesController::class, 'cultivosStore'])->name('enfermedades.cultivos.store');
    Route::get('enfermedades/{enfermedad}/cultivos/{tipoCultivo}/edit',     [EnfermedadesController::class, 'cultivosEdit'])->name('enfermedades.cultivos.edit');
    Route::put('enfermedades/{enfermedad}/cultivos/{tipoCultivo}',          [EnfermedadesController::class, 'cultivosUpdate'])->name('enfermedades.cultivos.update');
    Route::delete('enfermedades/{enfermedad}/cultivos/{tipoCultivo}',       [EnfermedadesController::class, 'cultivosDestroy'])->name('enfermedades.cultivos.destroy');
    // Textura de suelo
    Route::prefix('textura-suelo')->group(function () {
        Route::get('/', [TipoSueloController::class, 'index'])->name('textura-suelo.index');
        Route::post('/update', [TipoSueloController::class, 'updateSuelos'])->name('textura-suelo.update');
    });
    // Estación
    Route::resource('estaciones', EstacionesController::class);
    Route::get('/parcelas-por-cliente',                     [HomeController::class, 'parcelasPorCliente'])->name('parcelas.cliente');
    Route::get('/zonas-por-parcela',                        [HomeController::class, 'zonasPorParcela'])->name('zonas.parcela');
    Route::get('/estacion-virtual-get',                     [HomeController::class, 'ZonaManejosGet'])->name('estacion_virtual.get');
    Route::get('/etapas_fenologicas_por_tipo_cultivo',      [HomeController::class, 'etapasFenologicasPorTipoDeCultivo'])->name('etapas_fenologicas_tipo_cultivo');
    Route::get('/carga_datos_etapafenologica',              [HomeController::class, 'cargaDatosEtapafenologica'])->name('datos_x_etapa_fenologica');
    Route::get('/unidades-chart',                           [HomeController::class, 'unidadesChart'])->name('home.unidades_chart');
    Route::get('/grafica_temperatura_admosferica',          [HomeController::class, 'view_grafica_temperatura_admosferica'])->name('home.grafica_temperatura_admosferica');
    Route::get('/grafica_temperatura',                      [HomeController::class, 'grafica_temperatura'])->name('home.grafica_temperatura');
    Route::get('/grafica_estres',                           [HomeController::class, 'grafica_estres'])->name('home.grafica_estres');
    Route::get('/grafica_estres_pronostico',                    [HomeController::class, 'grafica_estres_pronostico'])->name('home.grafica_estres_pronostico');
    Route::get('/grafica_estres_pronostico_humedad_relativa',   [HomeController::class, 'grafica_estres_pronostico_humedad_relativa'])->name('home.grafica_estres_pronostico_humedad_relativa');
    Route::get('/grafica_estres_pronostico_velocidad_viento',   [HomeController::class, 'grafica_estres_pronostico_velocidad_viento'])->name('home.grafica_estres_pronostico_velocidad_viento');
    Route::get('/grafica_estres_pronostico_presion_atmosferica',   [HomeController::class, 'grafica_estres_pronostico_presion_atmosferica'])->name('home.grafica_estres_pronostico_presion_atmosferica');
    Route::get('/grafica_co2',                                  [HomeController::class, 'grafica_co2'])->name('home.grafica_co2');
    Route::get('/grafica_ph',                              [HomeController::class, 'grafica_ph'])->name('home.grafica_ph');
    Route::get('/grafica_nitrogeno',                       [HomeController::class, 'grafica_nitrogeno'])->name('home.grafica_nitrogeno');
    Route::get('/grafica_fosforo',                         [HomeController::class, 'grafica_fosforo'])->name('home.grafica_fosforo');
    Route::get('/grafica_potasio',                         [HomeController::class, 'grafica_potasio'])->name('home.grafica_potasio');
    Route::get('/grafica_conductividad_electrica',                  [HomeController::class, 'grafica_conductividad_electrica'])->name('home.grafica_conductividad_electrica');

    // Gráficas de Precipitación Pluvial
    Route::get('/grafica_precipitacion_pluvial',                    [HomeController::class, 'grafica_precipitacion_pluvial'])->name('home.grafica_precipitacion_pluvial');
    Route::get('/grafica_precipitacion_pluvial_acumulada',          [HomeController::class, 'grafica_precipitacion_pluvial_acumulada'])->name('home.grafica_precipitacion_pluvial_acumulada');
    Route::get('/tabla_precipitacion_pluvial',                      [HomeController::class, 'tabla_precipitacion_pluvial'])->name('home.tabla_precipitacion_pluvial');
    Route::get('/grafica_estres_precipitacion_pluvial',             [HomeController::class, 'grafica_estres_precipitacion_pluvial'])->name('home.grafica_estres_precipitacion_pluvial');
    Route::get('/grafica_estres_velocidad_viento',                  [HomeController::class, 'grafica_estres_velocidad_viento'])->name('home.grafica_estres_velocidad_viento');
    Route::get('/grafica_estres_presion_atmosferica',               [HomeController::class, 'grafica_estres_presion_atmosferica'])->name('home.grafica_estres_presion_atmosferica');

    Route::get('/grafica_estres_pronostico_precipitacion_pluvial',  [HomeController::class, 'grafica_estres_pronostico_precipitacion_pluvial'])->name('home.grafica_estres_pronostico_precipitacion_pluvial');
    Route::get('/grafica_humedad_relativa',                         [HomeController::class, 'grafica_humedad_relativa'])->name('home.grafica_humedad_relativa');
    Route::get('/grafica_velocidad_viento',                         [HomeController::class, 'grafica_velocidad_viento'])->name('home.grafica_velocidad_viento');
    Route::get('/grafica_presion_atmosferica',                      [HomeController::class, 'grafica_presion_atmosferica'])->name('home.grafica_presion_atmosferica');
    Route::get('/grafica_humedad_suelo',                            [HomeController::class, 'grafica_humedad_suelo'])->name('home.grafica_humedad_suelo');
    // PLAGAS
    Route::get('/grafica_plagas',                                   [HomeController::class, 'getPlagasGraficas'])->name('home.grafica_plagas');
    Route::get('/plagas-parcial',                                   [HomeController::class, 'plagasParcial'])->name('component_plagas_graficas');
    // END PLAGAS

    // ENFERMEDADES
    Route::get('/component-enfermedades',                           [HomeController::class, 'componentEnfermedades'])->name('component_enfermedades');
    Route::post('/api/enfermedades/grafica-acumulaciones',          [HomeController::class, 'apiGraficaAcumulaciones'])->name('api.enfermedades.grafica_acumulaciones');
    Route::get('/test-agrupacion',                                  [HomeController::class, 'testAgrupacion'])->name('test_agrupacion');
    Route::get('/debug-enfermedades',                               [HomeController::class, 'debugEnfermedades'])->name('debug_enfermedades');
    Route::get('/debug-enfermedades-public',                        [HomeController::class, 'debugEnfermedadesPublic'])->name('debug_enfermedades_public');

    // APIs para datos de enfermedades en JSON
    Route::get('/api/enfermedades/{enfermedadId}/datos',            [HomeController::class, 'apiEnfermedadDatos'])->name('api.enfermedades.datos');
    // Route::get('/api/enfermedades/tipo-cultivo/{tipoCultivoId}/datos', [HomeController::class, 'apiEnfermedadesTipoCultivoDatos'])->name('api.enfermedades.tipo_cultivo.datos');
    // END ENFERMEDADES
    Route::get('/grafica_temperatura_suelo',                        [HomeController::class, 'grafica_temperatura_suelo'])->name('home.grafica_temperatura_suelo');
    Route::get('/grafica_pronostico_horas',                         [HomeController::class, 'grafica_pronostico_horas'])->name('home.grafica_pronostico_horas');


    // GRÁFICAS
    Route::get('/grafica-temperatura-atmosferica/{zonaManejoId}',   [HomeController::class, 'graficaTemperaturaAtmosferica'])->name('component_grafica_temperatura_atmosferica');
    Route::get('/grafica-co2/{zonaManejoId}',                       [HomeController::class, 'graficaCO2'])->name('component_grafica_co2');
    Route::get('/grafica-velocidad-viento/{zonaManejoId}',          [HomeController::class, 'graficaVelocidadViento'])->name('component_grafica_velocidad_viento');
    Route::get('/grafica-presion-atmosferica/{zonaManejoId}',       [HomeController::class, 'graficaPresionAtmosferica'])->name('component_grafica_presion_atmosferica');
    Route::get('/grafica-ph/{zonaManejoId}',                        [HomeController::class, 'graficaPH'])->name('component_grafica_ph');
    Route::get('/grafica-nitrogeno/{zonaManejoId}',                 [HomeController::class, 'graficaNitrogeno'])->name('component_grafica_nitrogeno');
    Route::get('/grafica-temperatura-suelo/{zonaManejoId}',         [HomeController::class, 'graficaTemperaturaSuelo'])->name('component_grafica_temperatura_suelo');
    Route::get('/grafica-humedad-relativa/{zonaManejoId}',          [HomeController::class, 'graficaHumedadRelativaComponente'])->name('component_grafica_humedad_relativa');
    Route::get('/grafica-humedad-suelo/{zonaManejoId}',             [HomeController::class, 'graficaHumedadSueloComponente'])->name('component_grafica_humedad_suelo');
    Route::get('/grafica-fosforo/{zonaManejoId}',                   [HomeController::class, 'graficaFosforo'])->name('component_grafica_fosforo');
    Route::get('/grafica-precipitacion-pluvial/{zonaManejoId}',     [HomeController::class, 'graficaPrecipitacionPluvialComponente'])->name('component_grafica_precipitacion_pluvial');
    Route::get('/grafica-potasio/{zonaManejoId}',                   [HomeController::class, 'graficaPotasio'])->name('component_grafica_potasio');
    Route::get('/grafica-conductividad-electrica/{zonaManejoId}',   [HomeController::class, 'graficaConductividadElectrica'])->name('component_grafica_conductividad_electrica');
    // END GRÁFICAS

    // Clientes
    Route::resource('clientes', ClientesController::class);
    Route::get('clientes/{cliente}/grupos', [ClientesController::class, 'grupos'])->name('clientes.grupos');
    Route::post('clientes/{cliente}/grupos', [ClientesController::class, 'storeGrupos'])->name('clientes.grupos.store');
    Route::prefix('clientes/{id}')->group(function () {
        Route::resource('parcelas', ParcelasController::class);
        Route::get('usuarios/permissions/{user}',                                  [UsuariosController::class, 'permissions'])->name('usuarios.permissions');
        Route::post('usuarios/permissions/store',                                   [UsuariosController::class, 'UpdateRoleUser'])->name('usuarios.roles_store');
        Route::resource('usuarios',                                                 UsuariosController::class);

        // Ruta personalizada para zona de manejo
        Route::get('parcelas/{parcela_id}/zona_manejo',                             [ZonaManejosController::class, 'index'])->name('zona_manejo.index');
        Route::get('parcelas/{parcela_id}/zona_manejo/create',                      [ZonaManejosController::class, 'create'])->name('zona_manejo.create');
        Route::post('parcelas/{parcela_id}/zona_manejo/store',                      [ZonaManejosController::class, 'store'])->name('zona_manejo.store');
        Route::get('parcelas/{parcela_id}/zona_manejo/edit/{zona_manejo}',          [ZonaManejosController::class, 'edit'])->name('zona_manejo.edit');
        Route::put('parcelas/{parcela_id}/zona_manejo/update/{zona_manejo}',        [ZonaManejosController::class, 'update'])->name('zona_manejo.update');
        Route::delete('parcelas/{parcela_id}/zona_manejo/destroy/{zona_manejo}',    [ZonaManejosController::class, 'destroy'])->name('zona_manejo.destroy');
        Route::get('parcelas/{parcela_id}/zona_manejo/permissions/{zona_manejo}',   [ZonaManejosController::class, 'permissions'])->name('zona_manejo.permissions');
        Route::post('parcelas/{parcela_id}/zona_manejo/permissions/store',          [ZonaManejosController::class, 'StoreZonaManejosUser'])->name('store_zona_manejos_user.store');
    });

    // Asignación de parcelas a grupos
    Route::get('asignacion/zonas', [ZonaGrupoController::class, 'index'])->name('accesos.zonas.index');
    Route::get('asignacion/zonas/asignar', [ZonaGrupoController::class, 'assign'])->name('zonas.assign');
    Route::post('asignacion/zonas/quitar', [ZonaGrupoController::class, 'remove'])->name('zonas.remove');
    Route::post('asignacion/zonas/guardar', [ZonaGrupoController::class, 'store'])->name('zonas.store');

    //Ruta para obtener las zonas por predio
    Route::get('/predios/{predio}/zonas', [ZonaGrupoController::class, 'zonasByPredio']) ->name('predios.zonas');
    //Ruta para obtener predios por grupo
    Route::get('/grupos/{grupo}/predios', [UserGrupoController::class, 'prediosByGrupo']) ->name('grupos.predios');
    

    // Asignacion de zonas de manejo a grupos
    Route::get('asignacion/parcelas', [ParcelaGrupoController::class, 'index'])->name('accesos.parcelas.index');
    Route::get('asignacion/parcelas/asignar', [ParcelaGrupoController::class, 'assign'])->name('parcelas.assign');
    Route::post('asignacion/parcelas/guardar', [ParcelaGrupoController::class, 'store'])->name('parcelas.store');

    Route::post('asignacion/parcelas/quitar', [ParcelaGrupoController::class, 'remove'])->name('parcelas.remove');

    // Configuración de usuario, asignacion de grupos a usuario
    Route::get('asignacion/usuarios', [UserGrupoController::class, 'index'])->name('accesos.usuarios.index');
    Route::get('asignacion/usuarios/asignar', [UserGrupoController::class, 'assign'])->name('asignacion.usuarios.assign');
    Route::post('asignacion/usuarios/guardar', [UserGrupoController::class, 'store'])->name('asignacion.usuarios.store');
    Route::post('asignacion/usuarios/quitar', [UserGrupoController::class, 'remove'])->name('asignacion.usuarios.remove');

    Route::get('/user-settings', [UserSettingsController::class, 'show'])->name('user-settings');
    Route::post('/user-settings', [UserSettingsController::class, 'store'])->name('user-settings.store');
    // EXPORTAR DATOS DE ESTACION
    Route::get('/exportar-estacion-dato',       [EstacionDatoExport::class, 'export'])->name('exportar-estacion-dato');
    Route::get('/exportar-estacion-dato-all',        [EstacionDatoExport::class, 'exportAll'])->name('exportar-estacion-dato-all');
    Route::get('/exportar-estacion-dato-all-optimized', [EstacionDatoExport::class, 'exportAllOptimized'])->name('exportar-estacion-dato-all-optimized');
    Route::get('/exportar-estacion-dato-all-csv',    [EstacionDatoExport::class, 'exportAllCSV'])->name('exportar-estacion-dato-all-csv');
    Route::get('/check-export-progress',        [EstacionDatoExport::class, 'checkExportProgress'])->name('check-export-progress');
    Route::get('/forecast/guarda',              [ForeCastController::class, 'guardaPronostico'])->name('forecast.guarda');

    Route::get('/forecast/parcela/{parcela}',           [ForeCastController::class, 'pronostico'])->name('forecast.pronostico');
    Route::get('/export-debug',                         [EstacionDatoExport::class, 'export']);
    Route::get('component_grafica_variables_multiples/{zonaManejoId}', [HomeController::class, 'view_grafica_variables_multiples'])->name('component_grafica_variables_multiples');

    //Rutas de usuario aparte clientes.
    Route::prefix('usuarios')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('usuarios.index');
        Route::get('/create', [UserController::class, 'create'])->name('usuarios.create');
        Route::post('/', [UserController::class, 'store'])->name('usuarios.store');
        Route::get('/{usuario}', [UserController::class, 'show'])->name('usuarios.show');
        Route::get('/{usuario}/edit', [UserController::class, 'edit'])->name('usuarios.edit');
        Route::put('/{usuario}', [UserController::class, 'update'])->name('usuarios.update');
        Route::delete('/{usuario}', [UserController::class, 'destroy'])->name('usuarios.destroy');
    });
});
require __DIR__ . '/auth.php';

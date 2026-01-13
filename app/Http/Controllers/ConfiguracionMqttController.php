<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionMqttUsuario;
use App\Models\ConfiguracionMqttLog;
use App\Models\Estaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Str;

class ConfiguracionMqttController extends Controller
{
    /**
     * Mostrar formulario de login
     */
    public function showLogin()
    {
        return view('configuracion-mqtt.login');
    }

    /**
     * Procesar login
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $usuario = ConfiguracionMqttUsuario::verificarCredenciales(
            $request->username,
            $request->password
        );

        if ($usuario) {
            $request->session()->put('configuracion_mqtt_authenticated', true);
            $request->session()->put('configuracion_mqtt_usuario_id', $usuario->id);
            $request->session()->put('configuracion_mqtt_username', $usuario->username);

            // Registrar log de login exitoso
            ConfiguracionMqttLog::crearLog(
                $usuario->id,
                $usuario->username,
                'login',
                "Usuario {$usuario->username} inició sesión exitosamente",
                null,
                $request->ip(),
                $request->userAgent()
            );

            return redirect()->route('configuracion-mqtt.index');
        }

        // Registrar log de intento de login fallido
        ConfiguracionMqttLog::crearLog(
            null,
            $request->username ?? 'desconocido',
            'login_fallido',
            "Intento de login fallido con usuario: " . ($request->username ?? 'desconocido'),
            null,
            $request->ip(),
            $request->userAgent()
        );

        return back()->withErrors([
            'username' => 'Las credenciales proporcionadas no son correctas.',
        ])->withInput($request->only('username'));
    }

    /**
     * Cerrar sesión
     */
    public function logout(Request $request)
    {
        $usuarioId = $request->session()->get('configuracion_mqtt_usuario_id');
        $username = $request->session()->get('configuracion_mqtt_username', 'desconocido');

        // Registrar log de logout
        if ($usuarioId) {
            ConfiguracionMqttLog::crearLog(
                $usuarioId,
                $username,
                'logout',
                "Usuario {$username} cerró sesión",
                null,
                $request->ip(),
                $request->userAgent()
            );
        }

        $request->session()->forget('configuracion_mqtt_authenticated');
        $request->session()->forget('configuracion_mqtt_usuario_id');
        $request->session()->forget('configuracion_mqtt_username');
        return redirect()->route('configuracion-mqtt.login');
    }

    /**
     * Mostrar pantalla de configuración
     */
    public function index(Request $request)
    {
        $usuarioId = $request->session()->get('configuracion_mqtt_usuario_id');
        $username = $request->session()->get('configuracion_mqtt_username', 'desconocido');

        // Obtener usuario para verificar permisos
        $usuario = $usuarioId ? ConfiguracionMqttUsuario::find($usuarioId) : null;

        // Obtener todas las estaciones activas
        $query = Estaciones::where('status', 1);

        // Si el usuario tiene restricciones de estaciones, filtrar
        if ($usuario && $usuario->estaciones_permitidas !== null) {
            $query->whereIn('id', $usuario->estaciones_permitidas);
        }

        $estaciones = $query->orderBy('uuid', 'asc')->get();

        // Registrar log de acceso a la pantalla
        ConfiguracionMqttLog::crearLog(
            $usuarioId,
            $username,
            'acceso_pantalla',
            "Usuario {$username} accedió a la pantalla de configuración",
            null,
            $request->ip(),
            $request->userAgent()
        );

        return view('configuracion-mqtt.index', [
            'section_name' => 'Configuración MQTT',
            'section_description' => 'Configuración de parámetros de dispositivos',
            'estaciones' => $estaciones,
            'usuario' => $usuario,
        ]);
    }

    /**
     * Mostrar logs de configuración MQTT
     */
    public function logs(Request $request)
    {
        $logs = ConfiguracionMqttLog::with('usuario')
            ->orderBy('id', 'desc')
            ->orderBy('created_at', 'desc')
            ->get(); // Usar get() en lugar de paginate() ya que DataTables maneja su propia paginación

        return view('configuracion-mqtt.logs', [
            'section_name' => 'Logs de Configuración MQTT',
            'section_description' => 'Registro de todas las acciones realizadas en la configuración MQTT',
            'logs' => $logs,
        ]);
    }

    /**
     * Enviar configuración MQTT
     */
    public function enviarConfiguracion(Request $request)
    {
        $request->validate([
            'estacion_id' => 'required|exists:estaciones,id',
            'PCF' => 'nullable|integer|min:0|max:2',
            'PCR' => 'nullable|integer|min:0|max:1',
            'PTP' => 'nullable|integer|min:0|max:7',
            'PTC' => 'nullable|integer|min:15|max:60',
            'PTR' => 'nullable|integer|min:0|max:23',
            'PRS' => 'nullable|integer|min:0|max:1',
        ]);

        try {
            $estacion = Estaciones::findOrFail($request->estacion_id);

            // Obtener usuario y verificar permisos
            $usuarioId = $request->session()->get('configuracion_mqtt_usuario_id');
            $usuario = $usuarioId ? ConfiguracionMqttUsuario::find($usuarioId) : null;

            // Validar permiso de estación
            if ($usuario && !$usuario->tienePermisoEstacion($estacion->id)) {
                return back()->withErrors([
                    'error' => 'No tiene permiso para modificar esta estación.',
                ])->withInput();
            }

            // Obtener parámetros enviados
            $parametrosEnviados = [
                'PCF' => $request->PCF,
                'PCR' => $request->PCR,
                'PTP' => $request->PTP,
                'PTC' => $request->PTC,
                'PTR' => $request->PTR,
                'PRS' => $request->PRS,
            ];

            // Aplicar permisos de parámetros (valores por defecto si no tiene permiso)
            $parametrosFinales = $usuario
                ? $usuario->aplicarPermisosParametros($parametrosEnviados)
                : $parametrosEnviados;

            // Construir el mensaje JSON
            $mensaje = json_encode([
                'PCF' => (int) $parametrosFinales['PCF'],
                'PCR' => (int) $parametrosFinales['PCR'],
                'PTP' => (int) $parametrosFinales['PTP'],
                'PTC' => (int) $parametrosFinales['PTC'],
                'PTR' => (int) $parametrosFinales['PTR'],
                'PRS' => (int) $parametrosFinales['PRS'],
            ], JSON_UNESCAPED_SLASHES);

            // Configuración MQTT
            $host = env('MQTT_HOST', '54.160.223.97');
            $port = (int) env('MQTT_PORT', 1883);
            $username = env('MQTT_USERNAME', 'iotuser');
            $password = env('MQTT_PASSWORD', 'Vx7@pLr9#qN2sZ1u');

            // El topic es: pap/{UUID} - usando uuid como identificador
            $topic = "pap/{$estacion->uuid}";
            $qos = 1; // Quality of Service nivel 1
            $retain = true; // Mensaje retenido

            // Conectar y publicar
            $clientId = 'pia_config_' . Str::random(6);
            $settings = (new ConnectionSettings)
                ->setUsername($username)
                ->setPassword($password)
                ->setKeepAliveInterval(60);

            $client = new MqttClient($host, $port, $clientId);
            $client->connect($settings, true);

            $client->publish($topic, $mensaje, $qos, $retain);

            $client->disconnect();

            Log::info('Configuración MQTT enviada exitosamente', [
                'estacion_id' => $estacion->id,
                'uuid' => $estacion->uuid,
                'topic' => $topic,
                'mensaje' => $mensaje,
            ]);

            // Registrar log de envío de configuración
            $username = $request->session()->get('configuracion_mqtt_username', 'desconocido');

            $datosAdicionales = [
                'estacion_id' => $estacion->id,
                'estacion_uuid' => $estacion->uuid,
                'topic' => $topic,
                'parametros_enviados' => $parametrosEnviados,
                'parametros_finales' => $parametrosFinales,
                'mensaje_mqtt' => $mensaje,
            ];

            ConfiguracionMqttLog::crearLog(
                $usuarioId,
                $username,
                'enviar_configuracion',
                "Usuario {$username} envió configuración MQTT a estación UUID: {$estacion->uuid}",
                $datosAdicionales,
                $request->ip(),
                $request->userAgent()
            );

            return back()->with('success', "Configuración enviada exitosamente a la estación (UUID: {$estacion->uuid})");
        } catch (\Exception $e) {
            Log::error('Error enviando configuración MQTT', [
                'error' => $e->getMessage(),
                'estacion_id' => $request->estacion_id,
            ]);

            return back()->withErrors([
                'error' => 'Error al enviar la configuración: ' . $e->getMessage(),
            ])->withInput();
        }
    }
}

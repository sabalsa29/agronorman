<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionMqttUsuario;
use App\Models\ConfiguracionMqttLog;
use App\Models\Estaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ConfiguracionMqttUsuarioController extends Controller
{
    /**
     * Listar todos los usuarios MQTT
     */
    public function index(Request $request)
    {
        $usuarios = ConfiguracionMqttUsuario::orderBy('created_at', 'desc')->get();
        
        // Registrar log de acceso
        $usuarioId = $request->session()->get('configuracion_mqtt_usuario_id');
        $username = $request->session()->get('configuracion_mqtt_username', 'desconocido');
        
        ConfiguracionMqttLog::crearLog(
            $usuarioId,
            $username,
            'acceso_lista_usuarios',
            "Usuario {$username} accedió a la lista de usuarios MQTT",
            null,
            $request->ip(),
            $request->userAgent()
        );
        
        return view('configuracion-mqtt.usuarios.index', [
            'section_name' => 'Gestión de Usuarios MQTT',
            'section_description' => 'Administrar usuarios y privilegios de configuración MQTT',
            'usuarios' => $usuarios,
        ]);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        $estaciones = Estaciones::where('status', 1)
            ->orderBy('uuid', 'asc')
            ->get();

        return view('configuracion-mqtt.usuarios.create', [
            'section_name' => 'Crear Usuario MQTT',
            'section_description' => 'Crear nuevo usuario para configuración MQTT',
            'estaciones' => $estaciones,
        ]);
    }

    /**
     * Guardar nuevo usuario
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:configuracion_mqtt_usuarios,username',
            'password' => 'required|string|min:6',
            'activo' => 'nullable|in:0,1',
            'estaciones_permitidas' => 'nullable|array',
            'estaciones_permitidas.*' => 'exists:estaciones,id',
            'parametros_permitidos' => 'nullable|array',
            'parametros_permitidos.PCF' => 'nullable',
            'parametros_permitidos.PCR' => 'nullable',
            'parametros_permitidos.PTP' => 'nullable',
            'parametros_permitidos.PTC' => 'nullable',
            'parametros_permitidos.PTR' => 'nullable',
            'parametros_permitidos.PRS' => 'nullable',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Normalizar el valor de activo
        // Si viene "1" significa que el checkbox está marcado, "0" o ausente significa desmarcado
        $activo = $request->input('activo') === '1' || $request->input('activo') === 1 || $request->input('activo') === true;

        $usuario = ConfiguracionMqttUsuario::create([
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'activo' => $activo,
            'estaciones_permitidas' => $request->estaciones_permitidas ? array_map('intval', $request->estaciones_permitidas) : null,
            'parametros_permitidos' => $request->has('todos_parametros') ? null : [
                'PCF' => $request->has('parametros_permitidos.PCF'),
                'PCR' => $request->has('parametros_permitidos.PCR'),
                'PTP' => $request->has('parametros_permitidos.PTP'),
                'PTC' => $request->has('parametros_permitidos.PTC'),
                'PTR' => $request->has('parametros_permitidos.PTR'),
                'PRS' => $request->has('parametros_permitidos.PRS'),
            ],
        ]);

        // Registrar log de creación
        $usuarioId = $request->session()->get('configuracion_mqtt_usuario_id');
        $username = $request->session()->get('configuracion_mqtt_username', 'desconocido');
        
        $datosAdicionales = [
            'usuario_creado_id' => $usuario->id,
            'usuario_creado_username' => $usuario->username,
            'activo' => $usuario->activo,
            'estaciones_permitidas' => $usuario->estaciones_permitidas,
            'parametros_permitidos' => $usuario->parametros_permitidos,
        ];

        ConfiguracionMqttLog::crearLog(
            $usuarioId,
            $username,
            'crear_usuario',
            "Usuario {$username} creó un nuevo usuario MQTT: {$usuario->username}",
            $datosAdicionales,
            $request->ip(),
            $request->userAgent()
        );

        return redirect()->route('configuracion-mqtt.usuarios.index')
            ->with('success', 'Usuario creado exitosamente');
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit($id)
    {
        $usuario = ConfiguracionMqttUsuario::findOrFail($id);
        $estaciones = Estaciones::where('status', 1)
            ->orderBy('uuid', 'asc')
            ->get();

        return view('configuracion-mqtt.usuarios.edit', [
            'section_name' => 'Editar Usuario MQTT',
            'section_description' => 'Editar usuario y privilegios de configuración MQTT',
            'usuario' => $usuario,
            'estaciones' => $estaciones,
        ]);
    }

    /**
     * Actualizar usuario
     */
    public function update(Request $request, $id)
    {
        $usuario = ConfiguracionMqttUsuario::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:configuracion_mqtt_usuarios,username,' . $id,
            'password' => 'nullable|string|min:6',
            'activo' => 'nullable|in:0,1',
            'estaciones_permitidas' => 'nullable|array',
            'estaciones_permitidas.*' => 'exists:estaciones,id',
            'parametros_permitidos' => 'nullable|array',
            'parametros_permitidos.PCF' => 'nullable',
            'parametros_permitidos.PCR' => 'nullable',
            'parametros_permitidos.PTP' => 'nullable',
            'parametros_permitidos.PTC' => 'nullable',
            'parametros_permitidos.PTR' => 'nullable',
            'parametros_permitidos.PRS' => 'nullable',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Normalizar el valor de activo
        // Si viene "1" significa que el checkbox está marcado, "0" o ausente significa desmarcado
        $activo = $request->input('activo') === '1' || $request->input('activo') === 1 || $request->input('activo') === true;

        $data = [
            'username' => $request->username,
            'activo' => $activo,
            'estaciones_permitidas' => $request->estaciones_permitidas ? array_map('intval', $request->estaciones_permitidas) : null,
            'parametros_permitidos' => $request->has('todos_parametros') ? null : [
                'PCF' => $request->has('parametros_permitidos.PCF'),
                'PCR' => $request->has('parametros_permitidos.PCR'),
                'PTP' => $request->has('parametros_permitidos.PTP'),
                'PTC' => $request->has('parametros_permitidos.PTC'),
                'PTR' => $request->has('parametros_permitidos.PTR'),
                'PRS' => $request->has('parametros_permitidos.PRS'),
            ],
        ];

        // Guardar datos anteriores para el log
        $datosAnteriores = [
            'username' => $usuario->username,
            'activo' => $usuario->activo,
            'estaciones_permitidas' => $usuario->estaciones_permitidas,
            'parametros_permitidos' => $usuario->parametros_permitidos,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $usuario->update($data);
        $usuario->refresh();

        // Registrar log de actualización
        $usuarioId = $request->session()->get('configuracion_mqtt_usuario_id');
        $username = $request->session()->get('configuracion_mqtt_username', 'desconocido');
        
        $datosAdicionales = [
            'usuario_editado_id' => $usuario->id,
            'usuario_editado_username' => $usuario->username,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => [
                'username' => $usuario->username,
                'activo' => $usuario->activo,
                'estaciones_permitidas' => $usuario->estaciones_permitidas,
                'parametros_permitidos' => $usuario->parametros_permitidos,
                'password_cambiado' => $request->filled('password'),
            ],
        ];

        ConfiguracionMqttLog::crearLog(
            $usuarioId,
            $username,
            'editar_usuario',
            "Usuario {$username} editó el usuario MQTT: {$usuario->username}",
            $datosAdicionales,
            $request->ip(),
            $request->userAgent()
        );

        return redirect()->route('configuracion-mqtt.usuarios.index')
            ->with('success', 'Usuario actualizado exitosamente');
    }

    /**
     * Eliminar usuario
     */
    public function destroy(Request $request, $id)
    {
        $usuario = ConfiguracionMqttUsuario::findOrFail($id);
        
        // Guardar datos antes de eliminar para el log
        $usernameEliminado = $usuario->username;
        $datosEliminados = [
            'id' => $usuario->id,
            'username' => $usuario->username,
            'activo' => $usuario->activo,
            'estaciones_permitidas' => $usuario->estaciones_permitidas,
            'parametros_permitidos' => $usuario->parametros_permitidos,
        ];

        $usuario->delete();

        // Registrar log de eliminación
        $usuarioId = $request->session()->get('configuracion_mqtt_usuario_id');
        $username = $request->session()->get('configuracion_mqtt_username', 'desconocido');
        
        ConfiguracionMqttLog::crearLog(
            $usuarioId,
            $username,
            'eliminar_usuario',
            "Usuario {$username} eliminó el usuario MQTT: {$usernameEliminado}",
            $datosEliminados,
            $request->ip(),
            $request->userAgent()
        );

        return redirect()->route('configuracion-mqtt.usuarios.index')
            ->with('success', 'Usuario eliminado exitosamente');
    }
}

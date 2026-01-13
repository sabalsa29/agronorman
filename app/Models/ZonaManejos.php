<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ZonaManejos extends Model
{
    use SoftDeletes;

    protected $table = 'zona_manejos';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'parcela_id',
        'grupo_id',
        'tipo_suelo_id',
        'nombre',
        'fecha_inicial_uca',
        'temp_base_calor',
        'edad_cultivo',
        'fecha_siembra'
    ];

    public function estaciones()
    {
        return $this->belongsToMany(
            Estaciones::class,                // Modelo relacionado
            'zona_manejos_estaciones',        // Tabla pivote
            'zona_manejo_id',                 // Clave foránea de este modelo
            'estacion_id'                     // Clave foránea del modelo relacionado
        );
    }

    public function tipoCultivos()
    {
        return $this->belongsToMany(
            TipoCultivos::class,           // modelo real
            'zona_manejos_tipo_cultivos', // tabla pivote
            'zona_manejo_id',             // foreign key del modelo actual
            'tipo_cultivo_id'             // foreign key del modelo relacionado
        )->withTimestamps()->withPivot('deleted_at');
    }

    public function parcela()
    {
        return $this->belongsTo(Parcelas::class);
    }

    public function tipo_suelo()
    {
        return $this->belongsTo(TipoSuelo::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'zona_manejos_user', 'zona_manejo_id', 'user_id')->withTimestamps()->withPivot('deleted_at');
    }

    /**
     * Relación: Una zona de manejo pertenece a un grupo (opcional)
     */
    public function grupo()
    {
        return $this->belongsTo(Grupos::class, 'grupo_id');
    }

    /**
     * Scope para filtrar zonas de manejo según el usuario autenticado
     * Prioridad:
     * 1. Super admin: ve todas las zonas de manejo
     * 2. Usuario con grupo asignado: ve zonas de manejo de su grupo y descendientes
     * 3. Usuario sin grupo pero con zonas asignadas: ve solo sus zonas asignadas
     * 4. Usuario sin grupo ni zonas: no ve nada
     */
    public function scopeForUser($query, $user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }

        // Si no hay usuario autenticado, no retornar nada
        if (!$user) {
            return $query->whereRaw('1 = 0'); // Query que no retorna resultados
        }

        // Si es super admin, puede ver todas las zonas de manejo
        if ($user->isSuperAdmin()) {
            return $query; // Sin filtro adicional
        }

        // Si el usuario tiene un grupo asignado, filtrar por grupo Y zonas asignadas directamente
        if ($user->grupo_id) {
            $grupoUsuario = $user->grupo;
            if ($grupoUsuario) {
                $gruposPermitidos = $grupoUsuario->obtenerDescendientes();
                // Incluir zonas del grupo y sus descendientes, O zonas asignadas directamente al usuario
                return $query->where(function ($q) use ($gruposPermitidos, $user) {
                    $q->whereIn('grupo_id', $gruposPermitidos)
                        ->orWhereHas('users', function ($subQ) use ($user) {
                            $subQ->where('users.id', $user->id)
                                ->whereNull('zona_manejos_user.deleted_at'); // Excluir soft deletes
                        });
                });
            }
        }

        // Si no tiene grupo, usar el filtro anterior (zonas asignadas directamente)
        return $query->whereHas('users', function ($q) use ($user) {
            $q->where('users.id', $user->id)
                ->whereNull('zona_manejos_user.deleted_at'); // Excluir soft deletes
        });
    }

    /**
     * Verificar si un usuario tiene acceso a esta zona de manejo
     * Prioridad:
     * 1. Super admin: acceso a todo
     * 2. Usuario con grupo: acceso si la zona pertenece a su grupo o descendientes
     * 3. Usuario sin grupo: acceso si está asignado directamente a la zona
     */
    public function userHasAccess($user = null): bool
    {
        if (!$user) {
            $user = auth()->user();
        }

        if (!$user) {
            return false;
        }

        // Super admin tiene acceso a todo
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Si el usuario tiene un grupo asignado, verificar por grupo
        if ($user->grupo_id && $this->grupo_id) {
            $grupoUsuario = $user->grupo;
            if ($grupoUsuario) {
                $gruposPermitidos = $grupoUsuario->obtenerDescendientes();
                if (in_array($this->grupo_id, $gruposPermitidos)) {
                    return true;
                }
            }
        }

        // Si no tiene grupo o la zona no está en su grupo, verificar asignación directa
        return $this->users()
            ->where('users.id', $user->id)
            ->whereNull('zona_manejos_user.deleted_at')
            ->exists();
    }

    public function getEdadCultivoAttribute()
    {
        if (!$this->fecha_siembra) {
            return null;
        }
        $fecha = Carbon::parse($this->fecha_siembra);
        $hoy = Carbon::now();

        // Obtener el tipo de vida desde el cultivo asociado al primer tipo de cultivo
        $tipoCultivo = $this->tipoCultivos()->first();
        if (!$tipoCultivo || !$tipoCultivo->cultivo) {
            return null;
        }
        $tipo_vida = $tipoCultivo->cultivo->tipo_vida;

        if ($tipo_vida == 2) { // Perene
            $diff = $fecha->diff($hoy);
            return $diff->y . ' años, ' . $diff->m . ' meses';
        } elseif ($tipo_vida == 1) { // Cíclica
            return $fecha->diffInDays($hoy) . ' días';
        }
        return null;
    }

    public function obtenerEstadoActual($zonaManejo)
    {
        // Forzar zona horaria correcta
        $fechaIni = Carbon::now('America/Mexico_City')->startOfDay()->format('Y-m-d 00:00:00');
        $fechaFin = Carbon::now('America/Mexico_City')->endOfDay()->format('Y-m-d 23:59:59');
        $fechaIniAnio = Carbon::now('America/Mexico_City')->startOfYear()->format('Y-m-d 00:00:00');

        $umbral_datos = Carbon::now('America/Mexico_City')->subDays(30);

        // Obtener la zona de manejo con sus relaciones
        $zonaManejoModel = $this->with('parcela.cliente', 'tipoCultivos', 'users')
            ->where('id', $zonaManejo)
            ->first();

        if (!$zonaManejoModel) {
            return ['data' => 'Zona de manejo no encontrada', 'status' => 404];
        }

        // Obtener IDs de estaciones asociadas a la zona de manejo
        $estaciones = $zonaManejoModel->estaciones;
        if ($estaciones->isEmpty()) {
            return ['data' => 'Sin estaciones asociadas', 'status' => 404];
        }

        $ids = $estaciones->pluck('id')->toArray();
        $resultado = array();

        foreach ($ids as $id) {
            $qDatos = "SELECT estacion_id, id_origen,
            (SELECT ROUND(MIN(temperatura),1) FROM estacion_dato WHERE estacion_id='" . $id . "' AND created_at>='" . $fechaIni . "' AND created_at<='" . $fechaFin . "') as temperatura_min,
            (SELECT ROUND(MAX(temperatura),1) FROM estacion_dato WHERE estacion_id='" . $id . "' AND created_at>='" . $fechaIni . "' AND created_at<='" . $fechaFin . "') as temperatura_max,
            (SELECT ROUND(AVG(temperatura),1) FROM estacion_dato WHERE estacion_id='" . $id . "' AND created_at>='" . $fechaIni . "' AND created_at<='" . $fechaFin . "') as temperatura_prom,
            IFNULL(radiacion_solar,'ND') as radiacion_solar,
            (SELECT ROUND(SUM(precipitacion_acumulada),2) FROM estacion_dato WHERE estacion_id='" . $id . "' AND created_at>='" . $fechaIni . "' AND created_at<='" . $fechaFin . "') as precipitacion_acumulada,
            (SELECT ROUND(SUM(precipitacion_acumulada),2) FROM estacion_dato WHERE estacion_id='" . $id . "' AND created_at>='" . $fechaIniAnio . "' AND created_at<='" . $fechaFin . "') as precipitacion_acumulada_anio,
            humedad_relativa,
            potencial_de_hidrogeno,
            ROUND(conductividad_electrica,2) as conductividad_electrica,
            ROUND(temperatura,1) as temperatura,
            alertas,
            humedad_15 as humedad_15,
            capacidad_productiva,
            direccion_viento,
            co2,
            ph as ph,
            phos as phos,
            nit as nit,
            pot as pot,
            ROUND(velocidad_viento,2) as velocidad_viento,
            ROUND(temperatura_suelo,1) as temperatura_suelo,
            ed.created_at,
            ed.updated_at            
            FROM estacion_dato ed 
            WHERE ed.estacion_id='" . $id . "'
            AND ed.created_at > '" . $umbral_datos . "'
            ORDER BY ed.created_at DESC LIMIT 1";

            $register = DB::select($qDatos);
            if (count($register) > 0)
                $resultado[] = (object) array_filter((array) $register[0], function ($val) {
                    return !is_null($val);
                });
        }


        if (count($resultado) > 0) {

            if (count($resultado) > 1)
                $resultado = (object) array_merge((array) $resultado[0],   (array)$resultado[1]);
            else
                $resultado = $resultado[0];

            foreach ($ids as $id) {
                //Consultamos las estaciones físicas que están involucradas
                $qEstaciones = "SELECT * FROM estaciones WHERE id='" . $id . "' ";
                $dEstaciones = DB::select($qEstaciones);
                $estacion = $dEstaciones[0];

                //Extraemos el nivel de batería y la última transmision
                $qEstatusEstacion = "SELECT TIMESTAMPDIFF(MINUTE, created_at, '" . date('Y-m-d H:i:s') . "') as ultima_transmision, bateria FROM estacion_dato WHERE estacion_id='" . $id . "' ORDER BY created_at DESC LIMIT 1";
                $dEstatusEstacion = DB::select($qEstatusEstacion);
                if (empty($dEstatusEstacion))
                    continue;
                $estatusEstacion = $dEstatusEstacion[0];
                $estatus = 0;
                $bateria = 0;

                //Determinamos el estatus de la transmision
                if ($estatusEstacion->ultima_transmision <= 30)
                    $estatus = 1;
                if ($estatusEstacion->ultima_transmision > 30 && $estatusEstacion->ultima_transmision <= 60)
                    $estatus = 2;
                if ($estatusEstacion->ultima_transmision > 60)
                    $estatus = 3;

                if ($estatusEstacion->bateria >= 30)
                    $bateria = 1;
                if ($estatusEstacion->bateria < 30 && $estatusEstacion->bateria >= 11)
                    $bateria = 2;
                if ($estatusEstacion->bateria < 11)
                    $bateria = 3;


                $resultado->estaciones[] = array('nombre' => $estacion->uuid, 'tipo' => $estacion->tipo_estacion_id, 'estatus' => $estatus, 'bateria' => $bateria, 'id' => $estacion->uuid);
            }
            $resultado->tipo_cultivo_id = $zonaManejoModel->tipoCultivos->pluck('id')->first();
            $resultado->estacion_id = $zonaManejo;
            $resultado->empresa = $zonaManejoModel->users[0]->cliente->nombre;
            $resultado->usuario = $zonaManejoModel->users[0]->nombre;
            $resultado->predio = $zonaManejoModel->parcela->nombre;
            $resultado->zona = $zonaManejoModel->nombre;
            if (!isset($resultado->velocidad_viento))
                $resultado->velocidad_viento = "ND";
            if (!isset($resultado->precipitacion_acumulada))
                $resultado->precipitacion_acumulada = "ND";
            return ['data' => $resultado, 'status' => 200];
        } else {
            return ['data' => 'Sin datos', 'status' => 200];
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grupos extends Model
{
    use SoftDeletes;

    protected $table = 'grupos';
    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'status',
        'grupo_id',
    ];

    /**
     * Relación: Un grupo pertenece a un grupo padre (opcional)
     */
    public function grupoPadre()
    {
        return $this->belongsTo(Grupos::class, 'grupo_id');
    }

    /**
     * Relación: Un grupo puede tener múltiples subgrupos
     */
    public function subgrupos()
    {
        return $this->hasMany(Grupos::class, 'grupo_id');
    }

    

    /**
     * Obtener la ruta completa del grupo (jerarquía completa)
     */
    public function getRutaCompletaAttribute()
    {
        //Ignorar si es_root
        //validar si $this->grupoPadre en is_root es true, si no es true, continuar
        if ($this->is_root) {
            return $this->nombre;
        }

        $ruta = $this->nombre;
        $padre = ($this->grupoPadre && !$this->grupoPadre->is_root)? $this->grupoPadre : null;

        while ($padre) {
            if($padre->nombre == 'Norman'){
                break;
            }
            $ruta = $padre->nombre . ' > ' . $ruta;
            $padre = $padre->grupoPadre;
        }

        return $ruta;
    }

    /**
     * Relación: Un grupo puede tener múltiples zonas de manejo
     */
    public function zonaManejos()
    {
        return $this->hasMany(ZonaManejos::class, 'grupo_id');
    }

    // public function predios()
    // {
    //     return $this->belongsToMany(Parcelas::class, 'grupo_predio', 'grupo_id', 'predio_id')
    //         ->withTimestamps();
    // }

    /**
     * Relación: Un grupo puede tener múltiples usuarios asignados
     */
    public function usuarios()
    {
        return $this->hasMany(User::class, 'grupo_id');
    }

    /**
     * Relación: Un grupo puede estar asignado a múltiples clientes
     */
    public function clientes()
    {
        return $this->belongsToMany(Clientes::class, 'cliente_grupo', 'grupo_id', 'cliente_id')
            ->withTimestamps();
    }

    /**
     * Obtener todos los grupos descendientes (recursivo)
     * Incluye el grupo actual y todos sus subgrupos en todos los niveles
     */
    public function obtenerDescendientes(): array
    {
        $descendientes = [$this->id];
        $this->obtenerDescendientesRecursivo($this, $descendientes);
        return $descendientes;
    }

    /**
     * Método recursivo para obtener todos los descendientes
     */
    private function obtenerDescendientesRecursivo(Grupos $grupo, &$descendientes)
    {
        // Cargar subgrupos si no están cargados
        if (!$grupo->relationLoaded('subgrupos')) {
            $grupo->load('subgrupos');
        }

        foreach ($grupo->subgrupos as $subgrupo) {
            if (!in_array($subgrupo->id, $descendientes)) {
                $descendientes[] = $subgrupo->id;
                $this->obtenerDescendientesRecursivo($subgrupo, $descendientes);
            }
        }
    }

    /**
     * Scope para filtrar grupos según el usuario autenticado
     * Si el usuario es super admin, puede ver todos los grupos
     * Si el usuario tiene un grupo asignado, solo ve su grupo y descendientes
     * Si el usuario no tiene grupo asignado, no ve ningún grupo (excepto super admin)
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

        // Si es super admin, puede ver todos los grupos
        if ($user->isSuperAdmin()) {
            return $query; // Sin filtro adicional
        }

        // Si el usuario no tiene grupo asignado, no puede ver ningún grupo
        if (!$user->grupo_id) {
            return $query->whereRaw('1 = 0');
        }

        // Obtener el grupo del usuario y todos sus descendientes
        $grupoUsuario = $user->grupo;
        if (!$grupoUsuario) {
            return $query->whereRaw('1 = 0');
        }

        $gruposPermitidos = $grupoUsuario->obtenerDescendientes();

        // Filtrar para mostrar solo el grupo del usuario y sus descendientes
        return $query->whereIn('id', $gruposPermitidos);
    }

    /**
     * Verificar si un usuario tiene acceso a este grupo
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

        // Si el usuario no tiene grupo asignado, no tiene acceso
        if (!$user->grupo_id) {
            return false;
        }

        // Obtener el grupo del usuario y todos sus descendientes
        $grupoUsuario = $user->grupo;
        if (!$grupoUsuario) {
            return false;
        }

        $gruposPermitidos = $grupoUsuario->obtenerDescendientes();

        // Verificar si este grupo está en la lista de permitidos
        return in_array($this->id, $gruposPermitidos);
    }
}

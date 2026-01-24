<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'cliente_id',
        'grupo_id',
        'nombre',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Clientes::class);
    }

    /**
     * Relación: Un usuario pertenece a un grupo (opcional)
     * El grupo determina qué parte de la jerarquía puede ver el usuario
     */
    public function grupo()
    {
        return $this->belongsTo(Grupos::class, 'grupo_id');
    }

    public function zona_manejos()
    {
        return $this->belongsToMany(
            ZonaManejos::class,
            'zona_manejos_user',
            'user_id',
            'zona_manejo_id'
        )->withTimestamps()->withPivot('deleted_at');
    }

    /**
     * Verificar si el usuario es super administrador 
     * Super admin: role_id = 1 y cliente_id = null
     */
    public function isSuperAdmin(): bool
    {
        return $this->role_id === 1 && $this->cliente_id === null;
    }

    /**
     * Relación: Un usuario puede tener múltiples permisos de menú
     */
    public function menuPermissions()
    {
        return $this->hasMany(UserMenuPermission::class);
    }

    /**
     * Verificar si el usuario tiene permiso para ver un menú principal
     */
    public function hasMenuPermission(string $menuKey): bool
    {
        // Super admin tiene acceso a todo
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Buscar permiso específico
        $permission = $this->menuPermissions()
            ->where('menu_key', $menuKey)
            ->first();

        // Si no existe permiso, por defecto está permitido (comportamiento actual)
        if (!$permission) {
            return true;
        }

        return $permission->permitted;
    }

    /**
     * Verificar si el usuario tiene permiso para ver un menú secundario
     */
    public function hasSubMenuPermission(string $parentKey, string $subMenuKey): bool
    {
        // Super admin tiene acceso a todo
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Primero verificar que la principal esté permitida
        if (!$this->hasMenuPermission($parentKey)) {
            return false; // Si la principal está bloqueada, no puede ver secundarias
        }

        // Buscar permiso específico de la secundaria
        // Puede tener parent_key igual a parentKey o a otro submenú padre (para submenús anidados)
        $permission = $this->menuPermissions()
            ->where('menu_key', $subMenuKey)
            ->first();

        // Si no existe permiso, por defecto está permitido (comportamiento actual)
        if (!$permission) {
            return true;
        }

        // Si el permiso está bloqueado, retornar false
        if (!$permission->permitted) {
            return false;
        }

        // Si el permiso tiene un parent_key diferente al parentKey directo, 
        // verificar que ese padre también esté permitido (para submenús anidados)
        if ($permission->parent_key && $permission->parent_key !== $parentKey) {
            // Verificar recursivamente el padre
            return $this->hasSubMenuPermission($parentKey, $permission->parent_key);
        }

        return true;
    }

    /**
     * Verificar si el usuario puede crear en un menú específico
     */
    public function canCreate(string $menuKey): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $permission = $this->menuPermissions()->where('menu_key', $menuKey)->first();
        if (!$permission) {
            return true; // Por defecto permitido
        }

        return $permission->permitted && $permission->can_create;
    }

    /**
     * Verificar si el usuario puede editar en un menú específico
     */
    public function canEdit(string $menuKey): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $permission = $this->menuPermissions()->where('menu_key', $menuKey)->first();
        if (!$permission) {
            return true; // Por defecto permitido
        }

        return $permission->permitted && $permission->can_edit;
    }

    /**
     * Verificar si el usuario puede eliminar en un menú específico
     */
    public function canDelete(string $menuKey): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $permission = $this->menuPermissions()->where('menu_key', $menuKey)->first();
        if (!$permission) {
            return true; // Por defecto permitido
        }

        return $permission->permitted && $permission->can_delete;
    }
}

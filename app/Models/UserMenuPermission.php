<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserMenuPermission extends Model
{
    protected $table = 'user_menu_permissions';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'menu_key',
        'menu_type',
        'parent_key',
        'permitted',
        'can_create',
        'can_edit',
        'can_delete',
    ];

    protected $casts = [
        'permitted' => 'boolean',
        'can_create' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
    ];

    /**
     * Relación: Un permiso pertenece a un usuario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

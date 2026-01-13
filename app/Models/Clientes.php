<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clientes extends Model
{
    /** @use HasFactory<\Database\Factories\ClientesFactory> */
    use HasFactory;
    use SoftDeletes;
    protected $table = 'clientes';
    public $timestamps = true;
    protected $fillable = [
        'nombre',
        'empresa',
        'ubicacion',
        'telefono',
        'status',
    ];
    public function users()
    {
        return $this->hasMany(User::class, 'cliente_id');
    }

    /**
     * Relación: Un cliente puede tener múltiples grupos asignados
     */
    public function grupos()
    {
        return $this->belongsToMany(Grupos::class, 'cliente_grupo', 'cliente_id', 'grupo_id')
            ->withTimestamps();
    }
}

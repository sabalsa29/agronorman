<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usuarios extends Model
{
    /** @use HasFactory<\Database\Factories\UsuariosFactory> */
    use HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'nombre',
        'email',
        'password',
        'cliente_id',
        'status',
    ];
    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'cliente_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role_id === 1 && $this->cliente_id === null;
    }



}

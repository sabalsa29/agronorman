<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cliente extends Model
{
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
        return $this->hasMany(User::class);
    }
}

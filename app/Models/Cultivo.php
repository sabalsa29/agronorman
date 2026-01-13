<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cultivo extends Model
{
    use SoftDeletes;
    protected $table = 'cultivos';

    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'descripcion',
        'imagen',
        'icono',
        'temp_base_calor',
        'tipo_vida'
    ];

    public function tipo_cultivos()
    {
        return $this->hasMany(TipoCultivos::class, 'cultivo_id');
    }
}

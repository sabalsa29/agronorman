<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VariablesMedicion extends Model
{
    use SoftDeletes;

    protected $table = 'variables_medicion';
    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'slug',
        'unidad',
    ];

    public function estaciones()
    {
        return $this->belongsToMany(Estaciones::class, 'estacion_variable');
    }
}

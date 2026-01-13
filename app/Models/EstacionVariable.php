<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstacionVariable extends Model
{
    protected $table = 'estacion_variable';
    public $timestamps = true;

    protected $fillable = [
        'estacion_id',
        'variables_medicion_id',
    ];

    public function estacion()
    {
        return $this->belongsTo(Estaciones::class, 'estacion_id');
    }

    public function variable_medicion()
    {
        return $this->belongsTo(VariablesMedicion::class, 'variables_medicion_id');
    }
}

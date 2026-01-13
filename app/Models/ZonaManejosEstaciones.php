<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ZonaManejosEstaciones extends Model
{
    use SoftDeletes;

    protected $table = 'zona_manejos_estaciones';

    public $timestamps = true;

    protected $fillable = [
        'estacion_id',
        'zona_manejo_id',
    ];

    public function estacion()
    {
        return $this->belongsTo(Estaciones::class);
    }

    public function zona_manejos()
    {
        return $this->belongsTo(ZonaManejos::class, 'zona_manejo_id');
    }
}

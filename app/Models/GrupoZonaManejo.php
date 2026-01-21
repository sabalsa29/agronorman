<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoZonaManejo extends Model
{
    protected $table = 'grupo_zona_manejo';

    public $timestamps = true; 

    protected $fillable = [
        'grupo_id',
        'zona_manejo_id',
        'created_at',
    ];

    public function grupo()
    {
        return $this->belongsTo(Grupos::class, 'grupo_id');
    }

    public function zona_manejo ()
    {
        return $this->belongsTo(ZonaManejos::class, 'zona_manejo_id');
    }
}

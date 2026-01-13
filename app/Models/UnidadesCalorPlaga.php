<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnidadesCalorPlaga extends Model
{
    protected $table = 'unidades_calor_plaga';
    protected $fillable = [
        'id',
        'zona_manejo_id',
        'plaga_id',
        'uc',
        'fecha',
        'created_at',
        'updated_at'
    ];

    public $timestamps = false;

    public function zonaManejo()
    {
        return $this->belongsTo(ZonaManejos::class, 'zona_manejo_id');
    }

    public function plaga()
    {
        return $this->belongsTo(Plaga::class, 'plaga_id');
    }
}

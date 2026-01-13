<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZonaManejosTipoCultivos extends Model
{
    protected $table = 'zona_manejos_tipo_cultivos';

    public $timestamps = true;

    protected $fillable = [
        'zona_manejo_id',
        'tipo_cultivo_id',
    ];

    public function tipo_cultivo()
    {
        return $this->belongsTo(TipoCultivos::class);
    }
    public function zona_manejo()
    {
        return $this->belongsTo(ZonaManejos::class);
    }
}

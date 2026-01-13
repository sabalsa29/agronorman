<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoCultivosPlaga extends Model
{
    protected $table = 'tipo_cultivos_plagas';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'tipo_cultivo_id',
        'plaga_id',
    ];
}

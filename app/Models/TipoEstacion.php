<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoEstacion extends Model
{
    use SoftDeletes;

    protected $table = 'tipo_estacions';
    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'tipo_nasa',
        'status'
    ];
}

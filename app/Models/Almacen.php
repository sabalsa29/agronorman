<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Almacen extends Model
{
    use SoftDeletes;

    protected $table = 'almacens';
    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'status',
    ];
}

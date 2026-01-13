<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fabricante extends Model
{
    use SoftDeletes;

    protected $table = 'fabricantes';
    public $timestamps = true;

    protected $fillable = [
        'id',
        'nombre',
        'status',
    ];
}

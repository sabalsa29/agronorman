<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZonaManejoLoteExterno extends Model
{
    protected $table = 'zona_manejo_lote_externo';

    protected $fillable = [
        'zona_manejo_id',
        'name',
        'externo_lote_id',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZonaManejoLoteIcamex extends Model
{
    protected $table = 'zona_manejo_lote_icamex';

    protected $fillable = [
        'zona_manejo_id',
        'name',
        'icamex_lote_id',
    ];
}

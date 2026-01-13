<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EtapaFenologica extends Model
{
    use SoftDeletes;

    protected $table = 'etapa_fenologicas';
    public $timestamps = true;

    protected $fillable = [
        'id',
        'nombre',
        'status',
    ];

    public function tipoCultivos()
    {
        return $this->belongsToMany(TipoCultivos::class, 'etapa_fenologica_tipo_cultivo')
            ->withTimestamps();
    }
}

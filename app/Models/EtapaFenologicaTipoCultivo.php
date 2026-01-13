<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtapaFenologicaTipoCultivo extends Model
{
    protected $table = 'etapa_fenologica_tipo_cultivo';

    protected $fillable = [
        'tipo_cultivo_id',
        'etapa_fenologica_id'
    ];

    public function etapaFenologica()
    {
        return $this->belongsTo(EtapaFenologica::class, 'etapa_fenologica_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    protected $table = 'user_settings';

    protected $fillable = [
        'zona_manejo_id',
        'tipo_cultivo_id',
        'etapa_fenologica_id',
    ];

    public function zonaManejo()
    {
        return $this->belongsTo(ZonaManejos::class, 'zona_manejo_id');
    }

    public function tipoCultivo()
    {
        return $this->belongsTo(TipoCultivos::class, 'tipo_cultivo_id');
    }

    public function etapaFenologica()
    {
        return $this->belongsTo(EtapaFenologica::class, 'etapa_fenologica_id');
    }
}

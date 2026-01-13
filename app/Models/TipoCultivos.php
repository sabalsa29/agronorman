<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Enfermedades;

class TipoCultivos extends Model
{
    use HasFactory;

    protected $table = 'tipo_cultivos';

    protected $fillable = [
        'cultivo_id',
        'nombre',
        'status'
    ];

    public function plagas()
    {
        return $this->belongsToMany(Plaga::class, 'tipo_cultivos_plagas', 'tipo_cultivo_id', 'plaga_id');
    }

    public function etapasFenologicas()
    {
        return $this->belongsToMany(EtapaFenologica::class, 'etapa_fenologica_tipo_cultivo', 'tipo_cultivo_id', 'etapa_fenologica_id');
    }

    // Alias para compatibilidad con el nombre usado en las vistas/controlador
    public function etapas_fenologicas()
    {
        return $this->etapasFenologicas();
    }

    public function cultivo()
    {
        return $this->belongsTo(Cultivo::class);
    }

    public function enfermedades()
    {
        return $this->belongsToMany(
            Enfermedades::class,
            'tipo_cultivos_enfermedades',
            'tipo_cultivo_id',
            'enfermedad_id'
        )->withPivot([
            'riesgo_humedad',
            'riesgo_humedad_max',
            'riesgo_temperatura',
            'riesgo_temperatura_max',
            'riesgo_medio',
            'riesgo_mediciones',
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plaga extends Model
{
    use SoftDeletes;

    protected $table = 'plagas';

    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'descripcion',
        'slug',
        'imagen',
        'posicion1',
        'posicion2',
        'posicion3',
        'posicion4',
        'posicion5',
        'posicion6',
        'umbral_min',
        'umbral_max',
        'unidades_calor_ciclo',
    ];

    public function cultivos()
    {
        return $this->belongsToMany(Cultivo::class);
    }

    public function semaforoPlaga($unidades, $plaga_id)
    {
        $plaga = Plaga::find($plaga_id);

        if ($unidades >= $plaga->posicion1 && $unidades < $plaga->posicion2)
            return [
                "color"      => '#00A14B',
                "etapa"      => 'Gestación',
                "porcentaje" => round(($unidades / $plaga->unidades_calor_ciclo) * 100, 1),
                "unidades" => $unidades
            ];

        if ($unidades >= $plaga->posicion2 && $unidades < $plaga->posicion3)
            return [
                "color"      => '#CAD022',
                "etapa"      => 'Gestación',
                "porcentaje" => round(($unidades / $plaga->unidades_calor_ciclo) * 100, 1),
                "unidades" => $unidades
            ];

        if ($unidades >= $plaga->posicion3 && $unidades < $plaga->posicion4)
            return [
                "color"      => '#FEBC17',
                "etapa"      => 'Control',
                "porcentaje" => round(($unidades / $plaga->unidades_calor_ciclo) * 100, 1),
                "unidades" => $unidades
            ];

        if ($unidades >= $plaga->posicion4 && $unidades < $plaga->posicion5)
            return [
                "color"      => '#F6941F',
                "etapa"      => 'Control',
                "porcentaje" => round(($unidades / $plaga->unidades_calor_ciclo) * 100, 2),
                "unidades" => $unidades
            ];

        if ($unidades >= $plaga->posicion5 && $unidades < $plaga->posicion6)
            return [
                "color"      => '#EF4136',
                "etapa"      => 'Daño',
                "porcentaje" => round(($unidades / $plaga->unidades_calor_ciclo) * 100, 2),
            ];

        if ($unidades >= $plaga->posicion6)
            return [
                "color"      => '#BD1E2C',
                "etapa"      => 'Daño',
                "porcentaje" => round(($unidades / $plaga->unidades_calor_ciclo) * 100, 2),
                "unidades" => $unidades
            ];
    }

    public function semaforoPlagaFor($unidades, $plaga_id)
    {
        $plaga = Plaga::find($plaga_id);
        $ucDesdeUltimoCiclo = fmod($unidades, $plaga->unidades_calor_ciclo);
        $unidadPorcentaje = number_format(($ucDesdeUltimoCiclo / $plaga->unidades_calor_ciclo) * 100, 1);

        if ($unidades >= $plaga->posicion1 && $unidades < $plaga->posicion2)
            return [
                "color"      => '#00A14B',
                "etapa"      => 'Gestación',
                "porcentaje" => $unidadPorcentaje,
                "unidades" => $ucDesdeUltimoCiclo
            ];

        if ($unidades >= $plaga->posicion2 && $unidades < $plaga->posicion3)
            return [
                "color"      => '#CAD022',
                "etapa"      => 'Gestación',
                "porcentaje" => $unidadPorcentaje,
                "unidades" => $ucDesdeUltimoCiclo
            ];

        if ($unidades >= $plaga->posicion3 && $unidades < $plaga->posicion4)
            return [
                "color"      => '#FEBC17',
                "etapa"      => 'Control',
                "porcentaje" => $unidadPorcentaje,
                "unidades" => $ucDesdeUltimoCiclo
            ];

        if ($unidades >= $plaga->posicion4 && $unidades < $plaga->posicion5)
            return [
                "color"      => '#F6941F',
                "etapa"      => 'Control',
                "porcentaje" => $unidadPorcentaje,
                "unidades" => $ucDesdeUltimoCiclo
            ];

        if ($unidades >= $plaga->posicion5 && $unidades < $plaga->posicion6)
            return [
                "color"      => '#EF4136',
                "etapa"      => 'Daño',
                "porcentaje" => $unidadPorcentaje,
                "unidades" => $ucDesdeUltimoCiclo
            ];

        if ($unidades >= $plaga->posicion6)
            return [
                "color"      => '#BD1E2C',
                "etapa"      => 'Daño',
                "porcentaje" => $unidadPorcentaje,
                "unidades" => $ucDesdeUltimoCiclo
            ];
    }
}

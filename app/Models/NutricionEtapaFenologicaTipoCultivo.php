<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class NutricionEtapaFenologicaTipoCultivo extends Model
{
    protected $table = 'nutricion_etapa_fenologica_tipo_cultivo';
    public $timestamps = true;

    protected $fillable = [
        'etapa_fenologica_tipo_cultivo_id',
        'variable',
        'min',
        'optimo_min',
        'optimo_max',
        'max',
    ];

    public function cultivo()
    {
        return $this->belongsTo(Cultivo::class);
    }

    public static function semaforoNutricionTemperatura($tipo_cultivo_id, $etapa_id, $variable, $valor)
    {
        $nutricionEtapa = EtapaFenologicaTipoCultivo::where('tipo_cultivo_id', $tipo_cultivo_id)
            ->where('etapa_fenologica_id', $etapa_id)
            ->first();
        if (!$nutricionEtapa) {
            return "#000"; // color por defecto si no hay datos
        }
        $datoNutricion = NutricionEtapaFenologicaTipoCultivo::where('variable', $variable)
            ->where('etapa_fenologica_tipo_cultivo_id', $nutricionEtapa->id)
            ->first();

        if ($datoNutricion === NULL) {
            return "#FFF"; // color por defecto si no hay datos
        }

        if ($valor < $datoNutricion->min) {
            return '#1976d2'; // azul fuerte
        } elseif ($valor >= $datoNutricion->min && $valor < $datoNutricion->optimo_min) {
            return '#4fc3f7'; // azul claro
        } elseif ($valor >= $datoNutricion->optimo_min && $valor <= $datoNutricion->optimo_max) {
            return '#00913F'; // verde
        } elseif ($valor > $datoNutricion->optimo_max && $valor <= $datoNutricion->max) {
            return '#FFFF00'; // amarillo
        } elseif ($valor > $datoNutricion->max) {
            return '#FF0000'; // rojo
        } else {
            return "#FFF"; // fallback
        }
    }

    public static function semaforoNutricion($tipo_cultivo_id, $etapa_id, $variable, $valor)
    {
        $nutricionEtapa = EtapaFenologicaTipoCultivo::where('tipo_cultivo_id', $tipo_cultivo_id)
            ->where('etapa_fenologica_id', $etapa_id)
            ->first();
        if (!$nutricionEtapa) {
            return "#000"; // color por defecto si no hay datos
        }
        $datoNutricion = NutricionEtapaFenologicaTipoCultivo::where('variable', $variable)
            ->where('etapa_fenologica_tipo_cultivo_id', $nutricionEtapa->id)
            ->first();

        if ($datoNutricion === NULL) {
            return "#FFF"; // color por defecto si no hay datos
        }

        if ($valor < $datoNutricion->min) {
            return '#FF0000'; // rojo
        } elseif ($valor >= $datoNutricion->min && $valor < $datoNutricion->optimo_min) {
            return '#FFFF00'; // amarillo
        } elseif ($valor >= $datoNutricion->optimo_min && $valor <= $datoNutricion->optimo_max) {
            return '#00913F'; // verde
        } elseif ($valor > $datoNutricion->optimo_max && $valor <= $datoNutricion->max) {
            return '#FFFF00'; // amarillo
        } elseif ($valor > $datoNutricion->max) {
            return '#FF0000'; // rojo
        } else {
            return "#FFF"; // fallback
        }
    }

    public static function semaforoNutricionArray($tipo_cultivo_id, $etapa_id, $variable, $valores)
    {
        $nutricionEtapa = EtapaFenologicaTipoCultivo::where('tipo_cultivo_id', $tipo_cultivo_id)
            ->where('etapa_fenologica_id', $etapa_id)
            ->first();
        if (!$nutricionEtapa) {
            return array_fill(0, count($valores), "#000"); // color por defecto si no hay datos
        }
        $datoNutricion = NutricionEtapaFenologicaTipoCultivo::where('variable', $variable)
            ->where('etapa_fenologica_tipo_cultivo_id', $nutricionEtapa->id)
            ->first();

        if ($datoNutricion === NULL) {
            return array_fill(0, count($valores), "#000"); // color por defecto si no hay datos
        }

        $colores = [];
        foreach ($valores as $valor) {
            if ($valor < $datoNutricion->min) {
                $colores[] = '#1976d2'; // azul fuerte
            } elseif ($valor >= $datoNutricion->min && $valor < $datoNutricion->optimo_min) {
                $colores[] = '#4fc3f7'; // azul claro
            } elseif ($valor >= $datoNutricion->optimo_min && $valor <= $datoNutricion->optimo_max) {
                $colores[] = '#4caf50'; // verde
            } elseif ($valor > $datoNutricion->optimo_max && $valor <= $datoNutricion->max) {
                $colores[] = '#ffeb3b'; // amarillo
            } elseif ($valor > $datoNutricion->max) {
                $colores[] = '#e53935'; // rojo
            } else {
                $colores[] = "#000"; // fallback
            }
        }

        return $colores;
    }

    public static function semaforoNutricionAgrupado($tipo_cultivo_id, $etapa_id, $variable, $valores)
    {
        $nutricionEtapa = EtapaFenologicaTipoCultivo::where('tipo_cultivo_id', $tipo_cultivo_id)
            ->where('etapa_fenologica_id', $etapa_id)
            ->first();
        if (!$nutricionEtapa) {
            return [
                'muy_bajo' => 0,
                'bajo' => 0,
                'optimo' => 0,
                'alto' => 0,
                'muy_alto' => 0
            ];
        }
        $datoNutricion = NutricionEtapaFenologicaTipoCultivo::where('variable', $variable)
            ->where('etapa_fenologica_tipo_cultivo_id', $nutricionEtapa->id)
            ->first();

        if ($datoNutricion === NULL) {
            return [
                'muy_bajo' => 0,
                'bajo' => 0,
                'optimo' => 0,
                'alto' => 0,
                'muy_alto' => 0
            ];
        }

        $conteo = [
            'muy_bajo' => 0,
            'bajo' => 0,
            'optimo' => 0,
            'alto' => 0,
            'muy_alto' => 0
        ];

        // Log temporal para depuración
        Log::info('semaforoNutricionAgrupado', [
            'tipo_cultivo_id' => $tipo_cultivo_id,
            'etapa_id' => $etapa_id,
            'variable' => $variable,
            'valores' => $valores
        ]);

        foreach ($valores as $valor) {
            // Si el valor es null, vacío o no numérico, lo contamos como muy_bajo
            if ($valor === null || $valor === '' || !is_numeric($valor)) {
                $conteo['muy_bajo']++;
                continue;
            }
            if ($valor < $datoNutricion->min) {
                $conteo['muy_bajo']++;
            } elseif ($valor >= $datoNutricion->min && $valor < $datoNutricion->optimo_min) {
                $conteo['bajo']++;
            } elseif ($valor >= $datoNutricion->optimo_min && $valor <= $datoNutricion->optimo_max) {
                $conteo['optimo']++;
            } elseif ($valor > $datoNutricion->optimo_max && $valor <= $datoNutricion->max) {
                $conteo['alto']++;
            } elseif ($valor > $datoNutricion->max) {
                $conteo['muy_alto']++;
            } else {
                // Fallback: si por alguna razón no entra en ningún rango, lo sumamos a muy_bajo
                $conteo['muy_bajo']++;
            }
        }

        // Log temporal para depuración
        Log::info('semaforoNutricionAgrupado conteo', [
            'conteo' => $conteo,
            'total_valores' => count($valores)
        ]);

        // Asegurar que la suma sea igual al número de valores
        $suma = array_sum($conteo);
        $faltantes = count($valores) - $suma;
        if ($faltantes > 0) {
            $conteo['muy_bajo'] += $faltantes;
        }

        return $conteo;
    }
}

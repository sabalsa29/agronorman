<?php

namespace App\View\Components;

use App\Models\Forecast;
use App\Models\UnidadesCalorPlaga;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\View\Component;

class Plagas extends Component
{
    public $zonaManejo;
    public $tipoCultivo;
    public $periodo;
    public $startDate;
    public $endDate;
    /**
     * Create a new component instance.
     */
    public function __construct($zonaManejo, $tipoCultivo, $periodo, $startDate, $endDate)
    {
        $this->zonaManejo = $zonaManejo;
        $this->tipoCultivo = $tipoCultivo;
        $this->periodo = $periodo;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $fechas = $this->calcularPeriodo($this->periodo, $this->startDate, $this->endDate);
        $plagas = $this->tipoCultivo ? $this->tipoCultivo->plagas : collect();
        $promediosDiarios = [];
        $fechasPronosticos = [];
        $unidadesCalorPorPlaga = [];

        if ($this->zonaManejo && $this->tipoCultivo) {
            foreach ($plagas as $plaga) {
                $hoy = now()->toDateString();
                $ucInicial = UnidadesCalorPlaga::where('zona_manejo_id', $this->zonaManejo->id)
                    ->where('plaga_id', $plaga->id)
                    ->whereDate('fecha', '<', $hoy)
                    ->sum('uc');
                $unidadesTotal = UnidadesCalorPlaga::where('zona_manejo_id', $this->zonaManejo->id)
                    ->where('plaga_id', $plaga->id)
                    ->sum('uc');
                $unidadesTotalFechaSiembra = UnidadesCalorPlaga::where('zona_manejo_id', $this->zonaManejo->id)
                    ->where('plaga_id', $plaga->id)
                    ->where('fecha', '>=', $this->zonaManejo->fecha_siembra)
                    ->sum('uc');
                $unidadesPeriodo = UnidadesCalorPlaga::where('zona_manejo_id', $this->zonaManejo->id)
                    ->where('plaga_id', $plaga->id)
                    ->where('fecha', '>=', $this->zonaManejo->fecha_siembra)
                    ->sum('uc');
                $unidadesDia = UnidadesCalorPlaga::where('zona_manejo_id', $this->zonaManejo->id)
                    ->where('plaga_id', $plaga->id)
                    ->orderBy('id', 'desc')
                    ->value('uc');
                $ucDesdeUltimoCiclo = $unidadesTotal % $plaga->unidades_calor_ciclo;
                $semaforo = $plaga->semaforoPlaga($ucDesdeUltimoCiclo, $plaga->id);

                $unidadesCalorPorPlaga[$plaga->id] = [
                    'unidadesCalor'         => $unidadesTotal,
                    'unidadesCalorDia'      => $unidadesDia,
                    'ucDesdeUltimoCiclo'    => $ucDesdeUltimoCiclo,
                    'semaforo'              => $semaforo,
                    'unidadesPeriodo'       => $unidadesPeriodo,
                    'unidadesTotalFechaSiembra' => $unidadesTotalFechaSiembra,
                ];
            }

            // Pronóstico
            $forecastPlague = new Forecast();
            $fechasPronosticos = [];
            foreach ($plagas as $plaga) {
                $fechasPronosticos[$plaga->id] = [];
                // Calcular $ucDesdeUltimoCicloPronostico para esta plaga
                $unidadesPeriodo = $unidadesCalorPorPlaga[$plaga->id]['unidadesPeriodo'] / $plaga->unidades_calor_ciclo;
                $ucDesdeUltimoCicloPronostico = round(
                    $unidadesCalorPorPlaga[$plaga->id]['unidadesTotalFechaSiembra'] -
                        floor($unidadesPeriodo) * $plaga->unidades_calor_ciclo,
                    2,
                );
                for ($day = 0; $day <= 5; $day++) {
                    $date = Carbon::now('America/Mexico_City')->addDays($day)->toDateString();
                    $fechasPronosticos[$plaga->id][$date] = $forecastPlague->statusPlagueByDate($date, $this->zonaManejo, $plaga);
                    $fechasPronosticos[$plaga->id]['ucDesdeUltimoCicloPronostico'] = $ucDesdeUltimoCicloPronostico;
                }
            }
        }

        return view('components.plagas', [
            'plagas' => $plagas,
            'zona_manejo' => $this->zonaManejo,
            'unidadesCalorPorPlaga' => $unidadesCalorPorPlaga,
            'promediosDiarios' => $promediosDiarios,
            'fechasPronosticos' => $fechasPronosticos,
        ]);
    }

    public function calcularPeriodo($periodo, $desdeR = null, $hastaR = null)
    {
        // Forzar zona horaria de México
        $desde = Carbon::now('America/Mexico_City')->format('Y-m-d H:i:s');
        $hasta = Carbon::now('America/Mexico_City')->subHours(24)->format('Y-m-d H:i:s'); // Valor por defecto
        $grupo = '4_horas';

        switch ($periodo) {
            case 1:
                $hasta = Carbon::now('America/Mexico_City')->subHours(24)->format('Y-m-d H:i:s');
                $grupo = '4_horas';
                break;
            case 2:
                $hasta = Carbon::now('America/Mexico_City')->subHours(48)->format('Y-m-d H:i:s');
                $grupo = '4_horas';
                break;
            case 3:
                $hasta = Carbon::now('America/Mexico_City')->subDays(7)->format('Y-m-d H:i:s');
                $grupo = 'd';
                break;
            case 4:
                $hasta = Carbon::now('America/Mexico_City')->subDays(14)->format('Y-m-d H:i:s');
                $grupo = 'd';
                break;
            case 5:
                $hasta = Carbon::now('America/Mexico_City')->subDays(30)->format('Y-m-d H:i:s');
                $grupo = 'd';
                break;
            case 6:
                $hasta = Carbon::now('America/Mexico_City')->subDays(60)->format('Y-m-d H:i:s');
                $grupo = 's';
                break;
            case 7:
                $hasta = Carbon::now('America/Mexico_City')->subDays(180)->format('Y-m-d H:i:s');
                $grupo = 's';
                break;
            case 8:
                $hasta = Carbon::now('America/Mexico_City')->subDays(365)->format('Y-m-d H:i:s');
                $grupo = 'm';
                break;
            case 9:
                if ($desdeR && $hastaR) {
                    $desde = $hastaR . " 23:59:59";
                    $hasta = $desdeR . " 00:00:00";
                } else {
                    $desde = Carbon::now('America/Mexico_City')->format('Y-m-d H:i:s');
                    $hasta = Carbon::now('America/Mexico_City')->subHours(24)->format('Y-m-d H:i:s');
                }
                $grupo = '4_horas';
                break;
            case 10:
                $hasta = Carbon::now('America/Mexico_City')->subHours(24)->format('Y-m-d H:i:s');
                $grupo = '4_horas';
                break;
            case 11:
                $hasta = Carbon::now('America/Mexico_City')->subHours(48)->format('Y-m-d H:i:s');
                $grupo = '4_horas';
                break;
            case 12:
                $hasta = Carbon::now('America/Mexico_City')->subDays(7)->format('Y-m-d H:i:s');
                $grupo = 'd';
                break;
            case 13:
                $hasta = Carbon::now('America/Mexico_City')->subDays(7)->format('Y-m-d H:i:s');
                $grupo = 'd';
                break;
            case 14:
                $hasta = Carbon::now('America/Mexico_City')->subDays(7)->format('Y-m-d H:i:s');
                $grupo = 'd';
                break;
            default:
                // Caso por defecto: últimas 24 horas
                $hasta = Carbon::now('America/Mexico_City')->subHours(24)->format('Y-m-d H:i:s');
                $grupo = '4_horas';
                break;
        }

        return array($desde, $hasta, $grupo);
    }

    public function calcularPeriodoFechas($periodo, $desdeR = null, $hastaR = null)
    {
        // Forzar zona horaria de México
        $now = Carbon::now('America/Mexico_City');

        // Por defecto: ayer completo
        $desde = $now->copy()->subDay()->startOfDay()->format('Y-m-d H:i:s');
        $hasta = $now->copy()->subDay()->endOfDay()->format('Y-m-d H:i:s');

        switch ($periodo) {
            case 1: // Ayer completo
                $desde = $now->copy()->subDay()->startOfDay()->format('Y-m-d H:i:s');
                $hasta = $now->copy()->subDay()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 2: // Antier + Ayer (últimos 2 días completos)
                $desde = $now->copy()->subDays(2)->startOfDay()->format('Y-m-d H:i:s');
                $hasta = $now->copy()->subDay()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 3: // Últimos 7 días completos
                $desde = $now->copy()->subDays(7)->startOfDay()->format('Y-m-d H:i:s');
                $hasta = $now->copy()->subDay()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 4: // Últimos 14 días completos
                $desde = $now->copy()->subDays(14)->startOfDay()->format('Y-m-d H:i:s');
                $hasta = $now->copy()->subDay()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 5: // Últimos 30 días completos
                $desde = $now->copy()->subDays(30)->startOfDay()->format('Y-m-d H:i:s');
                $hasta = $now->copy()->subDay()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 6: // Últimos 60 días completos
                $desde = $now->copy()->subDays(60)->startOfDay()->format('Y-m-d H:i:s');
                $hasta = $now->copy()->subDay()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 7: // Últimos 180 días completos
                $desde = $now->copy()->subDays(180)->startOfDay()->format('Y-m-d H:i:s');
                $hasta = $now->copy()->subDay()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 8: // Últimos 365 días completos
                $desde = $now->copy()->subDays(365)->startOfDay()->format('Y-m-d H:i:s');
                $hasta = $now->copy()->subDay()->endOfDay()->format('Y-m-d H:i:s');
                break;
            case 9: // Fechas personalizadas
                // Limpiar fechas si vienen con tiempo duplicado
                $desdeRLimpia = preg_replace('/\s+\d{2}:\d{2}:\d{2}$/', '', $desdeR);
                $hastaRLimpia = preg_replace('/\s+\d{2}:\d{2}:\d{2}$/', '', $hastaR);
                $desde = $desdeRLimpia . " 00:00:00";
                $hasta = $hastaRLimpia . " 23:59:59";
                break;
            case 10: // Próximas 24 horas
                $desde = $now->format('Y-m-d H:i:s');
                $hasta = $now->copy()->addHours(24)->format('Y-m-d H:i:s');
                break;
            case 11: // Próximas 48 horas
                $desde = $now->format('Y-m-d H:i:s');
                $hasta = $now->copy()->addHours(48)->format('Y-m-d H:i:s');
                break;
            case 12: // 24h antes + 48h después
                $desde = $now->copy()->subHours(24)->format('Y-m-d H:i:s');
                $hasta = $now->copy()->addHours(48)->format('Y-m-d H:i:s');
                break;
            case 13: // 48h antes + 48h después
                $desde = $now->copy()->subHours(48)->format('Y-m-d H:i:s');
                $hasta = $now->copy()->addHours(48)->format('Y-m-d H:i:s');
                break;
            case 14: // 7 días antes + 48h después
                $desde = $now->copy()->subDays(7)->format('Y-m-d H:i:s');
                $hasta = $now->copy()->addHours(48)->format('Y-m-d H:i:s');
                break;
            default:
                // Caso por defecto: ayer completo
                $desde = $now->copy()->subDay()->startOfDay()->format('Y-m-d H:i:s');
                $hasta = $now->copy()->subDay()->endOfDay()->format('Y-m-d H:i:s');
                break;
        }

        return array($hasta, $desde); // Retorna [hasta, desde] para mantener compatibilidad
    }
}

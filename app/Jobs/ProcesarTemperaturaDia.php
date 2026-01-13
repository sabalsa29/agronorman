<?php

namespace App\Jobs;

use App\Models\EstacionDato;
use App\Models\Plaga;
use App\Models\UnidadesCalorPlaga;
use App\Models\ZonaManejos;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcesarTemperaturaDia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $fecha;

    /**
     * Create a new job instance.
     */
    public function __construct($fecha = null)
    {
        // Si no se proporciona fecha, usar ayer
        $this->fecha = $fecha ? Carbon::parse($fecha) : Carbon::now('America/Mexico_City')->subDay();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Obtener el día completo (00:00:00 a 23:59:59)
        $inicioDia = $this->fecha->copy()->startOfDay();
        $finDia = $this->fecha->copy()->endOfDay();

        Log::info("Procesando temperatura para el día: {$this->fecha->format('Y-m-d')}");
        Log::info("Rango: {$inicioDia->format('Y-m-d H:i:s')} al {$finDia->format('Y-m-d H:i:s')}");

        echo "\n";
        echo "=== PROCESAMIENTO DE TEMPERATURA DEL DÍA ===\n";
        echo "Fecha: {$this->fecha->format('Y-m-d')}\n";
        echo "Rango: {$inicioDia->format('Y-m-d H:i:s')} al {$finDia->format('Y-m-d H:i:s')}\n";
        echo "==========================================\n\n";

        // Consultar todos los datos de estación del día
        $datosDia = EstacionDato::whereBetween('created_at', [$inicioDia, $finDia])
            ->get();
        $plagas = Plaga::all();

        if ($datosDia->isEmpty()) {
            echo "No se encontraron datos para el día {$this->fecha->format('Y-m-d')}\n";
            Log::info("No se encontraron datos para el día {$this->fecha->format('Y-m-d')}");
            return;
        }

        // Calcular estadísticas generales
        $temperaturaMaxima = $datosDia->max('temperatura');
        $temperaturaMinima = $datosDia->min('temperatura');
        $temperaturaPromedio = $datosDia->avg('temperatura');
        $totalRegistros = $datosDia->count();

        // Mostrar resultados generales
        echo "--- ESTADÍSTICAS GENERALES ---\n";
        echo "Total de registros: {$totalRegistros}\n";
        echo "Temperatura máxima: {$temperaturaMaxima}°C\n";
        echo "Temperatura mínima: {$temperaturaMinima}°C\n";
        echo "Temperatura promedio: " . round($temperaturaPromedio, 2) . "°C\n";
        echo "\n";

        // Obtener todas las zonas de manejo
        $zonasManejo = ZonaManejos::with('estaciones')->get();

        echo "--- DATOS POR ZONA DE MANEJO ---\n";
        foreach ($zonasManejo as $zona) {
            echo "--- ZONA: {$zona->nombre} (ID: {$zona->id}) ---\n";

            // Obtener IDs de estaciones relacionadas a esta zona
            $estacionIds = $zona->estaciones->pluck('id')->toArray();

            if (empty($estacionIds)) {
                echo "  - ⚠️  No hay estaciones asociadas a esta zona\n";
                continue;
            }

            // Filtrar datos de estación para esta zona específica
            $datosZona = $datosDia->whereIn('estacion_id', $estacionIds);

            if ($datosZona->isEmpty()) {
                echo "  - ⚠️  No se encontraron datos para esta zona\n";
                continue;
            }

            // Calcular estadísticas para esta zona
            $tempMaxZona = $datosZona->max('temperatura');
            $tempMinZona = $datosZona->min('temperatura');
            $tempPromZona = $datosZona->avg('temperatura');
            $totalRegistrosZona = $datosZona->count();

            // Validar que tenemos datos de temperatura válidos
            if ($tempMaxZona === null || $tempMinZona === null) {
                echo "  - ⚠️  Datos de temperatura incompletos para esta zona\n";
                continue;
            }

            echo "  - Estaciones: " . implode(', ', $estacionIds) . "\n";
            echo "  - Total registros: {$totalRegistrosZona}\n";
            echo "  - Temp máxima: {$tempMaxZona}°C\n";
            echo "  - Temp mínima: {$tempMinZona}°C\n";
            echo "  - Temp promedio: " . round($tempPromZona, 2) . "°C\n";

            foreach ($plagas as $plaga) {
                // Fórmula correcta: T_max = max(temperatura máxima, umbral_max)
                $tMax = max($tempMaxZona, $plaga->umbral_max);
                $tMin = $tempMinZona; // Temperatura mínima de la zona
                $tBase = $plaga->umbral_min;
                $ucPorPlaga = max(0, (($tMax + $tMin) / 2 - $tBase));

                // Buscar si ya existe un registro para esta combinación
                $unidadesCalorPlaga = UnidadesCalorPlaga::where('zona_manejo_id', $zona->id)
                    ->where('plaga_id', $plaga->id)
                    ->whereDate('fecha', $this->fecha->format('Y-m-d'))
                    ->first();

                if ($unidadesCalorPlaga) {
                    // Actualizar registro existente
                    $unidadesCalorPlaga->uc = $ucPorPlaga;
                    $unidadesCalorPlaga->updated_at = now();
                    $unidadesCalorPlaga->save();
                    echo "  - Plaga {$plaga->nombre}: UC = {$ucPorPlaga} (ACTUALIZADO)\n";
                } else {
                    // Crear nuevo registro
                    $unidadesCalorPlaga = new UnidadesCalorPlaga();
                    $unidadesCalorPlaga->zona_manejo_id = $zona->id; // ✅ ZONA CORRECTA
                    $unidadesCalorPlaga->plaga_id = $plaga->id;
                    $unidadesCalorPlaga->uc = $ucPorPlaga;
                    $unidadesCalorPlaga->fecha = $this->fecha;
                    $unidadesCalorPlaga->created_at = $this->fecha->format('Y-m-d H:i:s');
                    $unidadesCalorPlaga->updated_at = $this->fecha->format('Y-m-d H:i:s');
                    $unidadesCalorPlaga->save();
                    echo "  - Plaga {$plaga->nombre}: UC = {$ucPorPlaga} (NUEVO)\n";
                }
            }

            echo "\n";

            Log::info("Zona {$zona->nombre} (ID: {$zona->id}) - Día {$this->fecha->format('Y-m-d')}:");
            Log::info("Estaciones: " . implode(', ', $estacionIds));
            Log::info("Total registros: {$totalRegistrosZona}");
            Log::info("Temperatura máxima: {$tempMaxZona}°C");
            Log::info("Temperatura mínima: {$tempMinZona}°C");
            Log::info("Temperatura promedio: " . round($tempPromZona, 2) . "°C");
        }

        echo "==========================================\n";
        echo "PROCESAMIENTO COMPLETADO\n";
        echo "==========================================\n\n";
    }
}

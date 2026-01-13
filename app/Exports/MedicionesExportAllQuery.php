<?php

namespace App\Exports;

use App\Models\EstacionDato;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

class MedicionesExportAllQuery implements FromQuery, WithHeadings, WithChunkReading, WithMapping, ShouldAutoSize, WithBatchInserts, WithCalculatedFormulas
{
    protected $ids;

    public function __construct($ids)
    {
        $this->ids = $ids;
    }

    public function query()
    {
        return EstacionDato::query()
            ->selectRaw('
                DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as fecha,
                MAX(temperatura) as max_temperatura,
                MIN(temperatura) as min_temperatura,
                ROUND(AVG(temperatura), 2) as avg_temperatura,
                MAX(co2) as max_co2,
                MIN(co2) as min_co2,
                ROUND(AVG(co2), 2) as avg_co2,
                MAX(temperatura_suelo) as max_temperatura_suelo,
                MIN(temperatura_suelo) as min_temperatura_suelo,
                ROUND(AVG(temperatura_suelo), 2) as avg_temperatura_suelo,
                MAX(conductividad_electrica) as max_conductividad_electrica,
                MIN(conductividad_electrica) as min_conductividad_electrica,
                ROUND(AVG(conductividad_electrica), 2) as avg_conductividad_electrica,
                MAX(ph) as max_ph,
                MIN(ph) as min_ph,
                ROUND(AVG(ph), 2) as avg_ph,
                MAX(nit) as max_nit,
                MIN(nit) as min_nit,
                ROUND(AVG(nit), 2) as avg_nit,
                MAX(phos) as max_phos,
                MIN(phos) as min_phos,
                ROUND(AVG(phos), 2) as avg_phos,
                MAX(pot) as max_pot,
                MIN(pot) as min_pot,
                ROUND(AVG(pot), 2) as avg_pot
            ')
            ->whereIn('estacion_id', $this->ids)
            ->groupBy('fecha')
            ->orderBy('fecha');
    }

    public function headings(): array
    {
        return [
            'Fecha (Hora)',
            'Temperatura Máxima (°C)',
            'Temperatura Mínima (°C)',
            'Temperatura Promedio (°C)',
            'CO2 Máximo (ppm)',
            'CO2 Mínimo (ppm)',
            'CO2 Promedio (ppm)',
            'Temperatura Suelo Máxima (°C)',
            'Temperatura Suelo Mínima (°C)',
            'Temperatura Suelo Promedio (°C)',
            'Conductividad Eléctrica Máxima (Ds/m)',
            'Conductividad Eléctrica Mínima (Ds/m)',
            'Conductividad Eléctrica Promedio (Ds/m)',
            'pH Máximo',
            'pH Mínimo',
            'pH Promedio',
            'Nitrógeno Máximo (ppm)',
            'Nitrógeno Mínimo (ppm)',
            'Nitrógeno Promedio (ppm)',
            'Fósforo Máximo (ppm)',
            'Fósforo Mínimo (ppm)',
            'Fósforo Promedio (ppm)',
            'Potasio Máximo (ppm)',
            'Potasio Mínimo (ppm)',
            'Potasio Promedio (ppm)',
        ];
    }

    public function map($row): array
    {
        return [
            date('Y-m-d H:i:s', strtotime($row->fecha)),
            $row->max_temperatura ?? 'N/A',
            $row->min_temperatura ?? 'N/A',
            $row->avg_temperatura ?? 'N/A',
            $row->max_co2 ?? 'N/A',
            $row->min_co2 ?? 'N/A',
            $row->avg_co2 ?? 'N/A',
            $row->max_temperatura_suelo ?? 'N/A',
            $row->min_temperatura_suelo ?? 'N/A',
            $row->avg_temperatura_suelo ?? 'N/A',
            $row->max_conductividad_electrica ?? 'N/A',
            $row->min_conductividad_electrica ?? 'N/A',
            $row->avg_conductividad_electrica ?? 'N/A',
            $row->max_ph ?? 'N/A',
            $row->min_ph ?? 'N/A',
            $row->avg_ph ?? 'N/A',
            $row->max_nit ?? 'N/A',
            $row->min_nit ?? 'N/A',
            $row->avg_nit ?? 'N/A',
            $row->max_phos ?? 'N/A',
            $row->min_phos ?? 'N/A',
            $row->avg_phos ?? 'N/A',
            $row->max_pot ?? 'N/A',
            $row->min_pot ?? 'N/A',
            $row->avg_pot ?? 'N/A',
        ];
    }

    public function chunkSize(): int
    {
        return 100; // Reducido significativamente para ahorrar memoria
    }

    public function batchSize(): int
    {
        return 50; // Batch size pequeño para procesar en lotes pequeños
    }
}

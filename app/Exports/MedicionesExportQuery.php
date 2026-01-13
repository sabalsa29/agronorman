<?php

namespace App\Exports;

use App\Models\EstacionDato;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class MedicionesExportQuery implements FromQuery, WithHeadings, WithChunkReading
{
    protected $ids, $desde, $hasta;

    public function __construct($ids, $desde, $hasta)
    {
        $this->ids = $ids;
        $this->desde = $desde;
        $this->hasta = $hasta;
    }

    public function query()
    {
        return EstacionDato::query()
            ->whereIn('estacion_id', $this->ids)
            ->whereBetween('created_at', [$this->desde, $this->hasta]);
    }

    public function headings(): array
    {
        return [
            'id',
            'estacion_id',
            'id_origen',
            'radiacion_solar',
            'viento',
            'precipitacion_acumulada',
            'humedad_relativa',
            'potencial_de_hidrogeno',
            'conductividad_electrica',
            'temperatura',
            'temperatura_lvl1',
            'humedad_15',
            'direccion_viento',
            'velocidad_viento',
            'co2',
            'ph',
            'phos',
            'nit',
            'pot',
            'temperatura_suelo',
            'alertas',
            'capacidad_productiva',
            'bateria',
            'created_at',
            'updated_at',
            'deleted_at'
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}

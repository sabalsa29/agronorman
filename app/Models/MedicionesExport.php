<?php

namespace App\Models;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Carbon\Carbon;
use Maatwebsite\Excel\Events\AfterSheet;

class MedicionesExport implements FromView, WithEvents, WithDrawings
{
    protected $datos;
    protected $fechaInicio;
    protected $fechaFin;
    protected $zona_manejo;
    protected $tipo_cultivo;
    protected $parcela;
    protected $cliente;

    public function __construct($datos, $fechaInicio, $fechaFin, $zona_manejo, $tipo_cultivo, $parcela, $cliente)
    {
        $this->datos = $datos;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->zona_manejo = $zona_manejo;
        $this->tipo_cultivo = $tipo_cultivo;
        $this->parcela = $parcela;
        $this->cliente = $cliente;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Combinar celdas para encabezados
                $event->sheet->mergeCells('B5:I5'); // "PLATAFORMA DE INTELIGENCIA AGRONÓMICA"
                $event->sheet->getDelegate()->getStyle('B5')->getAlignment()->setHorizontal('center');
                $event->sheet->getDelegate()->getStyle('B5')->getFont()->setBold(true);
            },
        ];
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Logo Norman');
        $drawing->setDescription('Logo');
        $drawing->setPath(public_path('assets/images/logo_excel.jpeg')); // Ruta a tu logo
        $drawing->setHeight(90);
        $drawing->setCoordinates('K2'); // Posición de la imagen

        return [$drawing];
    }

    public function view(): View
    {
        // Establecer el locale en español
        Carbon::setLocale('es');

        return view('exports.estacion_dato', [
            'datos' => $this->datos,
            'fechaInicio' => Carbon::parse($this->fechaInicio)->locale('es_MX')->isoFormat('D [de] MMMM [de] YYYY'),
            'fechaFin' => Carbon::parse($this->fechaFin)->locale('es_MX')->isoFormat('D [de] MMMM [de] YYYY'),
            'zona_manejo' => $this->zona_manejo,
            'tipo_cultivo' => $this->tipo_cultivo,
            'parcela' => $this->parcela,
            'cliente' => $this->cliente,
            'fechaExportacion' => 'De: ' . Carbon::parse($this->fechaInicio)->locale('es_MX')->isoFormat('D [de] MMMM [de] YYYY') . ' a: ' . Carbon::parse($this->fechaFin)->locale('es_MX')->isoFormat('D [de] MMMM [de] YYYY'),
            'fechaUltimaTransmision' => now()->locale('es_MX')->isoFormat('D [de] MMMM [de] YYYY [a las] HH:mm'),
        ]);
    }
}

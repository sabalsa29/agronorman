<?php

namespace App\View\Components;

use Illuminate\View\Component;

class GraficaTemperaturaAdmosferica extends Component
{
    public $zonaManejo;
    public $periodo;
    public $startDate;
    public $endDate;
    public $tipoCultivoId;
    public $etapaFenologicaId;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($zonaManejo = null, $periodo = null, $startDate = null, $endDate = null, $tipoCultivoId = null, $etapaFenologicaId = null)
    {
        $this->zonaManejo = $zonaManejo;
        $this->periodo = $periodo;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->tipoCultivoId = $tipoCultivoId;
        $this->etapaFenologicaId = $etapaFenologicaId;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.grafica_temperatura_admosferica');
    }
}

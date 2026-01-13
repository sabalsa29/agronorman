<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="icon-aid-kit mr-2"></i>
                            Análisis de Enfermedades del Tipo de Cultivo
                        </h5>
                        @if ($tipoCultivoId)
                            <div class="text-right">
                                <span class="badge badge-info">
                                    <i class="icon-calendar mr-1"></i>
                                    Período:
                                    @switch($periodo)
                                        @case(1)
                                            Últimas 24 horas
                                        @break

                                        @case(2)
                                            Últimas 48 horas
                                        @break

                                        @case(3)
                                            Última semana
                                        @break

                                        @case(4)
                                            Últimas 2 semanas
                                        @break

                                        @case(5)
                                            Último mes
                                        @break

                                        @case(6)
                                            Último bimestre
                                        @break

                                        @case(7)
                                            Último semestre
                                        @break

                                        @case(8)
                                            Último año
                                        @break

                                        @case(9)
                                            Personalizado
                                        @break

                                        @case(10)
                                            Próximas 24 horas
                                        @break

                                        @case(11)
                                            Próximas 48 horas
                                        @break

                                        @case(12)
                                            Últimas 24h + Próximas 48h
                                        @break

                                        @case(13)
                                            Últimas 48h + Próximas 48h
                                        @break

                                        @case(14)
                                            Última semana + Próximas 48h
                                        @break

                                        @default
                                            Últimas 24 horas
                                    @endswitch
                                </span>
                                @if ($startDate && $endDate)
                                    <br>
                                    <small class="text-muted">
                                        {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y H:i') }} -
                                        {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y H:i') }}
                                    </small>
                                @endif
                            </div>
                        @endif
                    </div>
                    <div class="d-flex justify-content-end mb-2">
                        <button id="btn-recargar-enfermedades" type="button" class="btn btn-outline-primary btn-sm"
                            onclick="if (typeof window.cargarEnfermedades === 'function') { this.disabled = true; this.innerHTML='\u21bb Recargando...'; try { window.cargarEnfermedades(); } finally { setTimeout(()=>{ this.disabled=false; this.innerHTML='\u21bb Recargar enfermedades'; }, 1200); } } else { window.location.reload(); }">
                            ↻ Recargar enfermedades
                        </button>
                    </div>
                    <div class="tab-pane show active" id="justified-right-icon-tab1">
                        <div class="card-body">
                            <ul class="nav nav-pills nav-pills-bordered nav-justified">
                                @if ($tipoCultivoId)
                                    @if ($enfermedades->count() > 0)
                                        @foreach ($enfermedades as $index => $ef)
                                            <li class="nav-item">
                                                <a href="#tab-enfermedades-{{ $ef->enfermedad->id }}"
                                                    class="nav-link {{ $index === 0 ? 'active' : '' }}"
                                                    data-toggle="tab" data-enfermedad-id="{{ $ef->enfermedad->id }}"
                                                    onclick="refreshGraficaEnfermedad({{ $ef->enfermedad->id }})">
                                                    <span
                                                        class="h6 mb-0 font-weight-bold">{{ $ef->enfermedad->nombre }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    @endif
                                @endif
                            </ul>
                            <div class="tab-content">
                                @if ($tipoCultivoId)
                                    @if ($enfermedades->count() > 0)
                                        @foreach ($enfermedades as $index => $ef)
                                            @php

                                                $estado = $ef->obtenerEstado(
                                                    $ef->enfermedad->id,
                                                    $ef->tipo_cultivo_id,
                                                    $zonaId,
                                                    $periodo,
                                                    $startDate,
                                                    $endDate,
                                                );

                                                $historico = $ef->obtenerHistorico(
                                                    $ef->enfermedad->id,
                                                    $ef->tipo_cultivo_id,
                                                    $zonaId,
                                                    $periodo,
                                                    $startDate,
                                                    $endDate,
                                                );

                                                $pronostico = $ef->obtenerPronostico(
                                                    $ef->enfermedad->id,
                                                    $ef->tipo_cultivo_id,
                                                    $zonaId,
                                                );

                                                $horasPron = $ef->horasPronostico(
                                                    $ef->enfermedad->id,
                                                    $ef->tipo_cultivo_id,
                                                    $zonaId,
                                                );
                                                $semaforo = $ef->calcularSemaforoRiesgo(
                                                    $historico[0]['acumulado'] ?? 0,
                                                    $ef->enfermedad,
                                                );
                                                $semaforoPron = $ef->calcularSemaforoRiesgo(
                                                    $horasPron,
                                                    $ef->enfermedad,
                                                );
                                            @endphp
                                            <!-- Enfermedad: {{ $ef->enfermedad->nombre }} -->
                                            <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                                                id="tab-enfermedades-{{ $ef->enfermedad->id }}">
                                                <div class="mb-4">
                                                    <div class="row align-items-center mb-3">
                                                        <!-- Nombre de la enfermedad -->
                                                        <div class="col-md-3">
                                                            <h4 class="card-title text-primary font-weight-bold mb-0">
                                                                <i class="icon-aid-kit mr-2"></i>
                                                                {{ $ef->enfermedad->nombre }}
                                                                @if ($ef->enfermedad->status == 1)
                                                                    <span class="badge badge-success ml-2">Activa</span>
                                                                @else
                                                                    <span
                                                                        class="badge badge-secondary ml-2">Inactiva</span>
                                                                @endif
                                                            </h4>
                                                        </div>

                                                        <!-- Temperatura -->
                                                        <div class="col-md-2">
                                                            <div
                                                                class="text-center p-2 border border-info rounded bg-light">
                                                                <small class="text-muted d-block">
                                                                    <i class="icon-thermometer mr-1"></i>Temperatura
                                                                </small>
                                                                <strong class="text-info">
                                                                    {{ $ef->riesgo_temperatura ?? 'N/A' }}° -
                                                                    {{ $ef->riesgo_temperatura_max ?? 'N/A' }}°C
                                                                </strong>
                                                            </div>
                                                        </div>

                                                        <!-- Humedad -->
                                                        <div class="col-md-2">
                                                            <div
                                                                class="text-center p-2 border border-warning rounded bg-light">
                                                                <small class="text-muted d-block">
                                                                    <i class="icon-droplet mr-1"></i>Humedad
                                                                </small>
                                                                <strong class="text-warning">
                                                                    {{ $ef->riesgo_humedad ?? 'N/A' }}% -
                                                                    {{ $ef->riesgo_humedad_max ?? 'N/A' }}%
                                                                </strong>
                                                            </div>
                                                        </div>

                                                        <!-- Umbrales de riesgo -->
                                                        <div class="col-md-3">
                                                            <div
                                                                class="text-center p-2 border border-success rounded bg-light">
                                                                <small class="text-muted d-block">
                                                                    <i class="icon-shield mr-1"></i>Umbrales
                                                                </small>
                                                                <strong class="text-success">
                                                                    {{ $ef->riesgo_medio ?? 'N/A' }}h /
                                                                    {{ $ef->riesgo_mediciones ?? 'N/A' }}h
                                                                </strong>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Tabla de Períodos de Condiciones y Gráfica -->
                                                    <div class="row mb-4">
                                                        <!-- Tabla de Períodos -->
                                                        <div class="col-md-12">
                                                            <div class="card">
                                                                <div class="card-header">
                                                                    <h6 class="card-title mb-0">
                                                                        <i class="icon-calendar mr-2"></i>
                                                                        Períodos de Condiciones para Brote de Enfermedad
                                                                    </h6>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="table-responsive"
                                                                        style="overflow-x: auto; overflow-y: auto; min-width: 100%; max-width: 100%; max-height: 465px;">
                                                                        <table class="table table-bordered table-hover"
                                                                            style="min-width: 600px; width: 100%;">
                                                                            <thead class="thead-dark">
                                                                                <tr>
                                                                                    <th style="width: 20%;"
                                                                                        class="text-center">
                                                                                        <i
                                                                                            class="icon-clock mr-1"></i>Tipo
                                                                                    </th>
                                                                                    <th style="width: 25%;"
                                                                                        class="text-center">
                                                                                        <i
                                                                                            class="icon-calendar mr-1"></i>Fecha
                                                                                        / riesgo
                                                                                    </th>
                                                                                    <th style="width: 15%;"
                                                                                        class="text-center">
                                                                                        <i
                                                                                            class="icon-checkmark mr-1"></i>Sin
                                                                                        riesgo
                                                                                    </th>
                                                                                    <th style="width: 15%;"
                                                                                        class="text-center">
                                                                                        <i
                                                                                            class="icon-warning mr-1"></i>Bajo
                                                                                    </th>
                                                                                    <th style="width: 15%;"
                                                                                        class="text-center">
                                                                                        <i
                                                                                            class="icon-danger mr-1"></i>Alto
                                                                                    </th>
                                                                                    <th style="width: 10%;"
                                                                                        class="text-center">
                                                                                        <i
                                                                                            class="icon-calculator mr-1"></i>Total
                                                                                    </th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                @php
                                                                                    // Obtener datos de pronósticos
                                                                                    $pronosticoEnfermedad = $pronosticosEnfermedades->get(
                                                                                        $ef->enfermedad->id,
                                                                                        collect(),
                                                                                    );

                                                                                    // Obtener datos reales
                                                                                    $datosRealesEnfermedad = $datosRealesEnfermedades->get(
                                                                                        $ef->enfermedad->id,
                                                                                        ['resultado' => collect()],
                                                                                    );

                                                                                    // Combinar todos los datos
                                                                                    $todosLosDatos = collect();
                                                                                    // Agregar pronósticos
                                                                                    foreach (
                                                                                        $pronosticoEnfermedad
                                                                                        as $item
                                                                                    ) {
                                                                                        $todosLosDatos->push([
                                                                                            'tipo' => 'Pronóstico',
                                                                                            'fecha_formateada' =>
                                                                                                $item[
                                                                                                    'fecha_formateada'
                                                                                                ],
                                                                                            'sin_riesgo' =>
                                                                                                $item['sin_riesgo'],
                                                                                            'bajo' => $item['bajo'],
                                                                                            'alto' => $item['alto'],
                                                                                            'total' => $item['total'],
                                                                                        ]);
                                                                                    }
                                                                                    // Agregar datos reales (históricos)
                                                                                    $resultadoReales =
                                                                                        $datosRealesEnfermedad[
                                                                                            'resultado'
                                                                                        ] ?? collect();
                                                                                    foreach (
                                                                                        $resultadoReales
                                                                                        as $item
                                                                                    ) {
                                                                                        $todosLosDatos->push([
                                                                                            'tipo' => 'Actual',
                                                                                            'fecha_formateada' =>
                                                                                                $item[
                                                                                                    'fecha_formateada'
                                                                                                ],
                                                                                            'sin_riesgo' =>
                                                                                                $item['sin_riesgo'],
                                                                                            'bajo' => $item['bajo'],
                                                                                            'alto' => $item['alto'],
                                                                                            'total' => $item['total'],
                                                                                            'detalle_horas' =>
                                                                                                $item[
                                                                                                    'detalle_horas'
                                                                                                ] ?? [],
                                                                                        ]);
                                                                                    }

                                                                                    // Ordenar por fecha (más reciente primero)
                                                                                    $todosLosDatos = $todosLosDatos
                                                                                        ->sortByDesc(function ($item) {
                                                                                            // Convertir fecha_formateada (dd-mm-yy) a Carbon para ordenamiento correcto
                                                                                            return \Carbon\Carbon::createFromFormat(
                                                                                                'd-m-y',
                                                                                                $item[
                                                                                                    'fecha_formateada'
                                                                                                ],
                                                                                            );
                                                                                        })
                                                                                        ->values();

                                                                                    // Calcular totales
                                                                                    $totalSinRiesgo = $todosLosDatos->sum(
                                                                                        'sin_riesgo',
                                                                                    );
                                                                                    $totalBajo = $todosLosDatos->sum(
                                                                                        'bajo',
                                                                                    );
                                                                                    $totalAlto = $todosLosDatos->sum(
                                                                                        'alto',
                                                                                    );
                                                                                    $totalGeneral = $todosLosDatos->sum(
                                                                                        'total',
                                                                                    );

                                                                                    // Calcular porcentajes
                                                                                    $porcentajeSinRiesgo =
                                                                                        $totalGeneral > 0
                                                                                            ? round(
                                                                                                ($totalSinRiesgo /
                                                                                                    $totalGeneral) *
                                                                                                    100,
                                                                                            )
                                                                                            : 0;
                                                                                    $porcentajeBajo =
                                                                                        $totalGeneral > 0
                                                                                            ? round(
                                                                                                ($totalBajo /
                                                                                                    $totalGeneral) *
                                                                                                    100,
                                                                                            )
                                                                                            : 0;
                                                                                    $porcentajeAlto =
                                                                                        $totalGeneral > 0
                                                                                            ? round(
                                                                                                ($totalAlto /
                                                                                                    $totalGeneral) *
                                                                                                    100,
                                                                                            )
                                                                                            : 0;
                                                                                @endphp

                                                                                @if ($todosLosDatos->count() > 0)
                                                                                    @foreach ($todosLosDatos as $item)
                                                                                        <tr
                                                                                            class="{{ $item['tipo'] == 'Pronóstico' ? 'table-warning' : 'table-info' }}">
                                                                                            <td class="text-center">
                                                                                                @if ($item['tipo'] == 'Pronóstico')
                                                                                                    <span
                                                                                                        class="badge badge-warning">
                                                                                                        <i
                                                                                                            class="icon-weather-cloudy mr-1"></i>Pronóstico
                                                                                                    </span>
                                                                                                @else
                                                                                                    <span
                                                                                                        class="badge badge-info">
                                                                                                        <i
                                                                                                            class="icon-database mr-1"></i>Actual
                                                                                                    </span>
                                                                                                @endif
                                                                                            </td>
                                                                                            <td
                                                                                                class="text-center font-weight-bold">
                                                                                                {{ $item['fecha_formateada'] }}
                                                                                            </td>
                                                                                            <td class="text-center">
                                                                                                <span
                                                                                                    class="badge badge-success">{{ $item['sin_riesgo'] }}h</span>
                                                                                            </td>
                                                                                            <td class="text-center">
                                                                                                <span
                                                                                                    class="badge badge-warning">{{ $item['bajo'] }}h</span>
                                                                                            </td>
                                                                                            <td class="text-center">
                                                                                                <span
                                                                                                    class="badge badge-danger">{{ $item['alto'] }}h</span>
                                                                                            </td>
                                                                                            <td
                                                                                                class="text-center font-weight-bold mt-4">
                                                                                                <span
                                                                                                    class="badge badge-primary">{{ $item['total'] }}h</span>
                                                                                                @if ($item['tipo'] == 'Actual' && isset($item['detalle_horas']) && count($item['detalle_horas']) > 0)
                                                                                                    <button
                                                                                                        class="btn btn-sm btn-outline-primary"
                                                                                                        type="button"
                                                                                                        onclick="mostrarDetalleHoras({{ $ef->enfermedad->id }}, '{{ $item['fecha_formateada'] }}', {{ json_encode($item['detalle_horas']) }}, {{ $ef->riesgo_temperatura ?? 'null' }}, {{ $ef->riesgo_temperatura_max ?? 'null' }}, {{ $ef->riesgo_humedad ?? 'null' }}, {{ $ef->riesgo_humedad_max ?? 'null' }}, {{ $ef->riesgo_medio ?? 'null' }}, {{ $ef->riesgo_mediciones ?? 'null' }})">
                                                                                                        <i
                                                                                                            class="icon-eye mr-1"></i>Ver
                                                                                                        Detalle por
                                                                                                        Horas
                                                                                                    </button>
                                                                                                @endif
                                                                                            </td>
                                                                                        </tr>
                                                                                    @endforeach

                                                                                    <!-- Fila de Total -->
                                                                                    <tr class="bg-light">
                                                                                        <td colspan="2"
                                                                                            class="text-center font-weight-bold">
                                                                                            Total
                                                                                        </td>
                                                                                        <td
                                                                                            class="text-center font-weight-bold">
                                                                                            {{ $totalSinRiesgo }}</td>
                                                                                        <td
                                                                                            class="text-center font-weight-bold">
                                                                                            {{ $totalBajo }}</td>
                                                                                        <td
                                                                                            class="text-center font-weight-bold">
                                                                                            {{ $totalAlto }}</td>
                                                                                        <td
                                                                                            class="text-center font-weight-bold">
                                                                                            {{ $totalGeneral }}</td>
                                                                                    </tr>

                                                                                    <!-- Fila de Porcentaje -->
                                                                                    <tr class="bg-light">
                                                                                        <td colspan="2"
                                                                                            class="text-center font-weight-bold">
                                                                                            Porcentaje</td>
                                                                                        <td
                                                                                            class="text-center font-weight-bold">
                                                                                            {{ $porcentajeSinRiesgo }}%
                                                                                        </td>
                                                                                        <td
                                                                                            class="text-center font-weight-bold">
                                                                                            {{ $porcentajeBajo }}%</td>
                                                                                        <td
                                                                                            class="text-center font-weight-bold">
                                                                                            {{ $porcentajeAlto }}%</td>
                                                                                        <td class="text-center"></td>
                                                                                    </tr>
                                                                                @else
                                                                                    <tr class="mt-4">
                                                                                        <td colspan="6"
                                                                                            class="text-center text-muted">
                                                                                            <i
                                                                                                class="icon-info mr-1"></i>No
                                                                                            hay datos
                                                                                            disponibles
                                                                                        </td>
                                                                                    </tr>
                                                                                @endif
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Gráfica de Estrés Histórico -->
                                                        <div class="col-12 mt-4">
                                                            <div class="card shadow-lg border-0">
                                                                <div
                                                                    class="card-header header-elements-inline bg-gradient-warning text-white">
                                                                    <h6 class="card-title mb-0">
                                                                        <i class="icon-stats-bars mr-2"></i>
                                                                        Análisis de Estrés por Enfermedad -
                                                                        {{ $ef->enfermedad->nombre }}
                                                                    </h6>
                                                                    <div class="header-elements">
                                                                        <div class="list-icons">
                                                                            <a class="list-icons-item"
                                                                                data-action="collapse"></a>
                                                                            <a class="list-icons-item"
                                                                                data-action="reload"></a>
                                                                            <a class="list-icons-item"
                                                                                data-action="remove"></a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="card-body">
                                                                    <div class="chart-container">
                                                                        <div class="chart has-fixed-height"
                                                                            id="columns_stacked_enfermedad_{{ $ef->enfermedad->id }}">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <!-- /gráfica de estrés histórico -->

                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    @else
                                        <div class="alert alert-info border-0">
                                            <div class="text-center">
                                                <i class="icon-info22 display-4 text-info mb-3"></i>
                                                <h5>No hay enfermedades activas</h5>
                                                <p class="text-muted">Este tipo de cultivo no tiene enfermedades
                                                    activas
                                                    configuradas en el
                                                    sistema.</p>
                                            </div>
                                        </div>
                                    @endif
                                @else
                                    <div class="alert alert-warning border-0">
                                        <div class="text-center">
                                            <i class="icon-warning22 display-4 text-warning mb-3"></i>
                                            <h5>Selecciona un tipo de cultivo</h5>
                                            <p class="text-muted">Para ver las enfermedades asociadas, primero debes
                                                seleccionar un
                                                tipo de cultivo.</p>
                                        </div>
                                    </div>
                                @endif


                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <!-- Modal para Detalle por Horas -->
    <div class="modal fade" id="modalDetalleHoras" tabindex="-1" role="dialog"
        aria-labelledby="modalDetalleHorasLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetalleHorasLabel">
                        <i class="icon-clock mr-2"></i>Detalle por Horas
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="contenidoDetalleHoras">
                        <!-- El contenido se llenará dinámicamente -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .chart-container {
            position: relative;
            width: 100%;
            height: 400px;
        }

        .chart {
            width: 100%;
            height: 100%;
        }

        .chart.has-fixed-height {
            min-height: 400px;
            width: 100%;
        }

        .bg-gradient-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
        }

        .card.shadow-lg {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card.shadow-lg:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1) !important;
        }

        .card-header h6 {
            font-weight: 600;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
        }
    </style>

    <script>
        // Función para refrescar la gráfica de una enfermedad específica
        function refreshGraficaEnfermedad(enfermedadId) {
            console.log('=== REFRESH GRÁFICA ENFERMEDAD ===');
            console.log('Refrescando gráfica para enfermedad:', enfermedadId);

            // Verificar que EchartsColumnsWaterfallsEnfermedades esté disponible
            if (typeof window.EchartsColumnsWaterfallsEnfermedades === 'undefined') {
                console.error('EchartsColumnsWaterfallsEnfermedades no está disponible');
                return;
            }

            // Destruir la gráfica existente si existe
            if (window.EchartsColumnsWaterfallsEnfermedades.destroy) {
                window.EchartsColumnsWaterfallsEnfermedades.destroy(enfermedadId);
                console.log('Gráfica destruida para enfermedad:', enfermedadId);
            }

            // Obtener los datos actuales desde la API
            let tipoCultivoId = window.filtros?.tipo_cultivo_id;
            let zonaId = window.filtros?.zona_manejo_id;
            let periodo = window.filtros?.periodo;
            let startDate = window.filtros?.startDate;
            let endDate = window.filtros?.endDate;

            if (!tipoCultivoId || !zonaId) {
                console.error('Filtros no disponibles para refrescar gráfica');
                return;
            }

            // Luego obtener los datos JSON de la API
            $.get("{{ route('api.enfermedades.tipo_cultivo.datos', ['tipoCultivoId' => ':tipoCultivoId']) }}"
                .replace(':tipoCultivoId', tipoCultivoId), {
                    zona_manejo_id: zonaId,
                    periodo: periodo,
                    startDate: startDate,
                    endDate: endDate
                },
                function(response) {
                    console.log('Datos de enfermedades obtenidos:', response);

                    if (response.success && response.data) {
                        // Cargar gráficas para cada enfermedad
                        cargarGraficasEnfermedades(response.data);
                    } else {
                        console.error('Error en respuesta de API:', response.message);
                    }

                    // Ocultar loader
                    ocultarLoader();
                }).fail(function(xhr, status, error) {
                console.error('Error al obtener datos de API:', error);
                console.error('Response:', xhr.responseText);
                // Ocultar loader en caso de error
                ocultarLoader();
            });
        }


        // Función para cargar gráficas de enfermedades con datos de la API
        function cargarGraficasEnfermedades(enfermedades) {
            console.log('Cargando gráficas para enfermedades:', enfermedades);

            // Verificar que EchartsColumnsWaterfallsEnfermedades esté disponible
            if (typeof window.EchartsColumnsWaterfallsEnfermedades === 'undefined') {
                console.error('EchartsColumnsWaterfallsEnfermedades no está disponible');
                return;
            }

            // Esperar un poco para asegurar que el DOM esté completamente cargado
            setTimeout(function() {
                console.log('Iniciando carga de gráficas después del delay...');

                // Cargar gráfica para cada enfermedad
                enfermedades.forEach(function(enfermedadData, index) {

                    // Usar la función updateData con los datos de la API y las fechas
                    window.EchartsColumnsWaterfallsEnfermedades.updateData(
                        enfermedadData.porcentajeSinRiesgo,
                        enfermedadData.porcentajeBajo,
                        enfermedadData.porcentajeAlto,
                        enfermedadData.totalGeneral,
                        enfermedadData.enfermedad_id,
                        enfermedadData.fechas
                    );
                });
            }, 500); // Esperar 500ms para que el DOM esté listo
        }

        // Hacer la función disponible globalmente
        window.refreshGraficaEnfermedad = refreshGraficaEnfermedad;

        // Función para mostrar el detalle por horas en un modal
        function mostrarDetalleHoras(enfermedadId, fecha, detalleHoras, tempMin, tempMax, humMin, humMax, riesgoMedio,
            riesgoMediciones) {
            console.log('Mostrando detalle por horas:', {
                enfermedadId: enfermedadId,
                fecha: fecha,
                detalleHoras: detalleHoras,
                parametros: {
                    tempMin,
                    tempMax,
                    humMin,
                    humMax,
                    riesgoMedio,
                    riesgoMediciones
                }
            });

            // Actualizar el título del modal
            document.getElementById('modalDetalleHorasLabel').innerHTML =
                '<i class="icon-clock mr-2"></i>Detalle por Horas - ' + fecha;

            // Generar el contenido HTML para el detalle de horas
            let contenido = '<h6 class="mb-3"><i class="icon-calendar mr-1"></i>' + fecha + '</h6>';

            // Agregar parámetros de la enfermedad
            contenido += '<div class="row mb-3">';
            contenido += '<div class="col-md-4">';
            contenido += '<div class="text-center p-2 border border-info rounded bg-light">';
            contenido += '<small class="text-muted d-block"><i class="icon-thermometer mr-1"></i>Temperatura</small>';
            contenido += '<strong class="text-info">' + (tempMin || 'N/A') + '° - ' + (tempMax || 'N/A') + '°C</strong>';
            contenido += '</div>';
            contenido += '</div>';
            contenido += '<div class="col-md-4">';
            contenido += '<div class="text-center p-2 border border-warning rounded bg-light">';
            contenido += '<small class="text-muted d-block"><i class="icon-droplet mr-1"></i>Humedad</small>';
            contenido += '<strong class="text-warning">' + (humMin || 'N/A') + '% - ' + (humMax || 'N/A') + '%</strong>';
            contenido += '</div>';
            contenido += '</div>';
            contenido += '<div class="col-md-4">';
            contenido += '<div class="text-center p-2 border border-success rounded bg-light">';
            contenido += '<small class="text-muted d-block"><i class="icon-shield mr-1"></i>Umbrales</small>';
            contenido += '<strong class="text-success">' + (riesgoMedio || 'N/A') + 'h / ' + (riesgoMediciones || 'N/A') +
                'h</strong>';
            contenido += '</div>';
            contenido += '</div>';
            contenido += '</div>';

            // Agregar leyenda de identificadores
            contenido += '<div class="alert alert-info mb-3">';
            contenido += '<div class="row">';
            contenido += '<div class="col-md-6">';
            contenido +=
                '<small><i class="icon-checkmark-circle text-success mr-1"></i><strong>Pasó por semáforo:</strong> Las condiciones fueron evaluadas por el sistema de riesgo</small>';
            contenido += '</div>';
            contenido += '<div class="col-md-6">';
            contenido +=
                '<small><i class="icon-minus-circle text-muted mr-1"></i><strong>No pasó por semáforo:</strong> Datos por defecto sin evaluación de riesgo</small>';
            contenido += '</div>';
            contenido += '</div>';
            contenido += '</div>';

            contenido += '<div class="row">';

            if (detalleHoras && detalleHoras.length > 0) {
                detalleHoras.forEach(function(hora) {
                    let badgeClass = 'badge-success';
                    if (hora.estatus === 'Bajo') {
                        badgeClass = 'badge-warning';
                    } else if (hora.estatus === 'Alto') {
                        badgeClass = 'badge-danger';
                    }

                    // Determinar si pasó por el semáforo
                    let pasoPorSemaforo = hora.condiciones_favorables === true;
                    let cardClass = pasoPorSemaforo ? 'card border-success shadow-sm' : 'card border-0 shadow-sm';
                    let indicadorSemaforo = pasoPorSemaforo ?
                        '<div class="position-absolute" style="top: 5px; right: 5px;">' +
                        '<i class="icon-checkmark-circle text-success" title="Pasó por semáforo"></i>' +
                        '</div>' :
                        '<div class="position-absolute" style="top: 5px; right: 5px;">' +
                        '<i class="icon-minus-circle text-muted" title="No pasó por semáforo"></i>' +
                        '</div>';

                    contenido += '<div class="col-md-3 col-sm-6 mb-3">';
                    contenido += '<div class="' + cardClass + '" style="position: relative;">';
                    contenido += indicadorSemaforo;
                    contenido += '<div class="card-body p-3">';
                    contenido += '<div class="d-flex justify-content-between align-items-center mb-2">';
                    contenido += '<span class="font-weight-bold text-primary">' + hora.hora + '</span>';
                    contenido += '<span class="badge ' + badgeClass + '">' + hora.estatus + '</span>';
                    contenido += '</div>';
                    contenido += '<div class="mb-1">';
                    contenido += '<small class="text-muted">';
                    contenido += '<i class="icon-thermometer mr-1"></i>' + hora.temperatura + '°C';
                    contenido += '</small>';
                    contenido += '</div>';
                    contenido += '<div>';
                    contenido += '<small class="text-muted">';
                    contenido += '<i class="icon-droplet mr-1"></i>' + hora.humedad + '%';
                    contenido += '</small>';
                    contenido += '</div>';
                    contenido += '</div>';
                    contenido += '</div>';
                    contenido += '</div>';
                });
            } else {
                contenido += '<div class="col-12">';
                contenido += '<div class="alert alert-info text-center">';
                contenido += '<i class="icon-info mr-1"></i>No hay datos de horas disponibles para esta fecha.';
                contenido += '</div>';
                contenido += '</div>';
            }

            contenido += '</div>';

            // Insertar el contenido en el modal
            document.getElementById('contenidoDetalleHoras').innerHTML = contenido;

            // Mostrar el modal
            $('#modalDetalleHoras').modal('show');
        }

        // Hacer la función disponible globalmente
        window.mostrarDetalleHoras = mostrarDetalleHoras;
    </script>
</div>

@extends('layouts.includ')

@section('content')
    @foreach ($plagas as $plaga)
        @php
            $unidadesPeriodo = $unidadesCalorPorPlaga[$plaga->id]['unidadesPeriodo'] % $plaga->unidades_calor_ciclo;
            $ucDesdeUltimoCiclo = $unidadesCalorPorPlaga[$plaga->id]['unidadesCalor'] % $plaga->unidades_calor_ciclo;
            $semaforo = $plaga->semaforoPlaga($ucDesdeUltimoCiclo, $plaga->id);
        @endphp
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">

                        <div class="row mt-3" style="background: #fff;">
                            <div class="col-12 col-md-4 mb-3 mb-md-0">
                                <h3 style="font-weight: bold;">{{ $plaga->nombre }}</h3>
                            </div>
                            <div class="col-12 col-md-2 mb-3 mb-md-0">
                                <div class="alert alert-primary" role="alert">
                                    <strong>UC por ciclo:</strong> {{ $plaga->unidades_calor_ciclo }}
                                </div>
                            </div>
                            <div class="col-12 col-md-3 mb-3 mb-md-0">
                                <div class="alert alert-primary" role="alert">
                                    <strong>Generaciones global:</strong>
                                    {{ floor($ucDesdeUltimoCiclo) }}
                                </div>
                            </div>
                            <div class="col-12 col-md-3 mb-3 mb-md-0">
                                <div class="alert alert-primary" role="alert">
                                    <strong>Generaciones periodo:</strong>
                                    {{ floor($unidadesPeriodo) }}
                                </div>
                            </div>
                        </div>

                        <div class="row overflow-hidden" style="background: #fff;">
                            <div class="col-12 col-md-4 mb-4 mb-md-0" style="height: 480px;">
                                <div class="chart w-100 h-100" id="gauge_custom_{{ $plaga->id }}"></div>
                            </div>

                            <div class="col-12 col-md-8">
                                <table class="table table-bordered mb-4">
                                    <thead>
                                        <tr>
                                            <th style="font-size: 16px; text-align: center;">UC desde el último ciclo</th>
                                            <th style="font-size: 16px; text-align: center;">UC desde la fecha de siembra
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="font-size: 14px; text-align: center;">{{ $ucDesdeUltimoCiclo }}</td>
                                            <td style="font-size: 14px; text-align: center;">
                                                {{ $unidadesCalorPorPlaga[$plaga->id]['ucPorPlaga'] }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <td colspan="2" style="font-size: 18px; text-align: center;"><b>Hoy</b></td>
                                            <td colspan="5" style="font-size: 18px; text-align: center;">
                                                <b>PRONÓSTICO</b>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th></th>
                                            <th>Hoy</th>
                                            @php
                                                $uCalorCiclo = $ucDesdeUltimoCiclo;
                                            @endphp
                                            @foreach ($fechasPronosticos as $fecha)
                                                <th style="text-align: center;">{{ $fecha['dayOfWeek'] }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th style="vertical-align: middle;">Unidades calor</th>
                                            @php
                                                // Calcular generación completa
                                                $genInicial = intdiv($uCalorCiclo, $plaga->unidades_calor_ciclo);
                                                // Si UC está por debajo del ciclo, residuo es 0, en caso contrario usamos fmod sobre $uCalorCiclo
                                                $residuoInicial = fmod($uCalorCiclo, $plaga->unidades_calor_ciclo);

                                                $semaforoDia = $plaga->semaforoPlaga(
                                                    number_format($residuoInicial, 2),
                                                    $plaga->id,
                                                );
                                            @endphp
                                            <th
                                                style="vertical-align: middle; background-color: {{ $semaforoDia['color'] }}; text-align: center;">
                                                <h1 style="color: #fff; font-size: 14px; font-weight: bold;">
                                                    {{ $residuoInicial }}
                                                </h1>
                                            </th>
                                            @foreach ($fechasPronosticos as $fecha)
                                                @php
                                                    $uCalorCiclo += $fecha['uc'];
                                                    $genDia = intdiv($uCalorCiclo, $plaga->unidades_calor_ciclo);
                                                    $residuoDia = fmod($uCalorCiclo, $plaga->unidades_calor_ciclo);
                                                    $semaforoDia = $plaga->semaforoPlaga(
                                                        number_format($residuoDia, 2),
                                                        $plaga->id,
                                                    );
                                                @endphp
                                                <th
                                                    style="vertical-align: middle; background-color: {{ $semaforoDia['color'] }}; text-align: center;">
                                                    <h1 style="color: #fff; font-size: 14px; font-weight: bold;">
                                                        {{ $residuoDia }}</h1>
                                                </th>
                                            @endforeach
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection

@push('scripts')
    {{-- 1) Expongo dos arreglos a JS:
          - plagasIds: sólo los IDs de cada plaga
          - semaforosData: semáforo con { porcentaje, etapa, color } para cada plaga.id --}}
    <script>
        window.plagasIds = @json($plagas->pluck('id'));
        window.semaforosData = @json(
            $plagas->mapWithKeys(function ($plaga) use ($unidadesCalorPorPlaga) {
                // Recalculamos el semáforo exactamente como hicimos arriba:
                $ucDesdeUltimo = $unidadesCalorPorPlaga[$plaga->id]['unidadesCalor'] % $plaga->unidades_calor_ciclo;
                $residuoInicial = fmod($ucDesdeUltimo, $plaga->unidades_calor_ciclo);
                $ucDesdeUltimoCiclo = $residuoInicial / $plaga->unidades_calor_ciclo;
                $m = $ucDesdeUltimoCiclo * 100;
                $semi = $plaga->semaforoPlaga($m, $plaga->id);
                return [$plaga->id => $semi];
            }));

        console.log('unidadesCalorPorPlaga', @json($unidadesCalorPorPlaga));
    </script>

    {{-- 2) Incluyo el JS que crea los gauges --}}
    @vite('resources/js/charts/plagas.js')
@endpush

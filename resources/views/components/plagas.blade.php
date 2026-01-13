<div>
    @foreach ($plagas as $plaga)
        @php
            $unidadesPeriodo = $unidadesCalorPorPlaga[$plaga->id]['unidadesPeriodo'] / $plaga->unidades_calor_ciclo;
            $ucDesdeUltimoCiclo = $unidadesCalorPorPlaga[$plaga->id]['unidadesCalor'] / $plaga->unidades_calor_ciclo;

            $ucDesdeUltimoCicloPronostico = round(
                $unidadesCalorPorPlaga[$plaga->id]['unidadesTotalFechaSiembra'] -
                    floor($unidadesPeriodo) * $plaga->unidades_calor_ciclo,
                2,
            );
            $uCalorCiclo = $ucDesdeUltimoCicloPronostico;
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
                                            <th style="font-size: 16px; text-align: center;">UC desde el último ciclo
                                            </th>
                                            <th style="font-size: 16px; text-align: center;">UC desde la fecha de
                                                siembra
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="font-size: 14px; text-align: center;">
                                                {{ number_format($ucDesdeUltimoCicloPronostico, 1) }}
                                            </td>
                                            <td style="font-size: 14px; text-align: center;">
                                                {{ number_format($unidadesCalorPorPlaga[$plaga->id]['unidadesTotalFechaSiembra'], 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>


                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <td colspan="7" style="font-size: 18px; text-align: center;">
                                                <b>PRONÓSTICO</b>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th></th>
                                            @if (isset($fechasPronosticos[$plaga->id]))
                                                @foreach (array_filter(
        $fechasPronosticos[$plaga->id],
        function ($key) {
            return $key !== 'ucDesdeUltimoCicloPronostico';
        },
        ARRAY_FILTER_USE_KEY,
    ) as $fecha)
                                                    <th style="text-align: center;">{{ $fecha['dayOfWeek'] }}</th>
                                                @endforeach
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <th style="vertical-align: middle;">Unidades calor</th>
                                            @if (isset($fechasPronosticos[$plaga->id]))
                                                @foreach (array_filter(
        $fechasPronosticos[$plaga->id],
        function ($key) {
            return $key !== 'ucDesdeUltimoCicloPronostico';
        },
        ARRAY_FILTER_USE_KEY,
    ) as $index => $fecha)
                                                    @php
                                                        $uCalorCiclo += $fecha['uc'];
                                                        $residuoDia = fmod($uCalorCiclo, $plaga->unidades_calor_ciclo);
                                                        $semaforoDia = $plaga->semaforoPlagaFor(
                                                            number_format($residuoDia, 1),
                                                            $plaga->id,
                                                        );
                                                    @endphp
                                                    <th
                                                        style="vertical-align: middle; background-color: {{ $semaforoDia['color'] }}; text-align: center;">
                                                        <h1 style="color: #fff; font-size: 14px; font-weight: bold;">
                                                            {{ number_format($residuoDia, 1) }}</h1>
                                                    </th>
                                                @endforeach
                                            @endif
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

    <script>
        window.plagasIds = @json($plagas->pluck('id'));
        window.semaforosData = @json(
            $plagas->mapWithKeys(function ($plaga) use ($fechasPronosticos) {
                $ucDesdeUltimo = $fechasPronosticos[$plaga->id]['ucDesdeUltimoCicloPronostico'];
                $ucDesdeUltimo = number_format($ucDesdeUltimo, 2);
                $semi = $plaga->semaforoPlagaFor($ucDesdeUltimo, $plaga->id);
                return [$plaga->id => $semi];
            }));
        console.log('fechasPronosticos', @json($fechasPronosticos));
        console.log('semaforosData', window.semaforosData);
    </script>
</div>

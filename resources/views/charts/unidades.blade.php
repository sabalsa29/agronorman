@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Unidades de Frío</h5>
                    </div>
                    <div class="card-body">
                        <h3>{{ $unidadesFrio }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Unidades de Calor</h5>
                    </div>
                    <div class="card-body">
                        <h3>{{ $unidadesCalor }}</h3>
                    </div>
                </div>
            </div>
        </div>

        @if ($resumen)
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Resumen de Temperaturas</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Temperatura Máxima:</strong> {{ $resumen->temp_max ?? 'N/A' }}</p>
                            <p><strong>Temperatura Mínima:</strong> {{ $resumen->temp_min ?? 'N/A' }}</p>
                            <p><strong>Amplitud Térmica:</strong> {{ $resumen->amplitud ?? 'N/A' }}</p>
                            <p><strong>Unidades de Calor:</strong> {{ $resumen->uc ?? 'N/A' }}</p>
                            <p><strong>Unidades de Frío:</strong> {{ $resumen->uf ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($desglose && $desglose->count() > 0)
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Desglose Diario</h5>
                        </div>
                        <div class="card-body">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Máx</th>
                                        <th>Mín</th>
                                        <th>Amplitud</th>
                                        <th>UC</th>
                                        <th>UF</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($desglose as $dato)
                                        <tr>
                                            <td>{{ $dato->fecha }}</td>
                                            <td>{{ $dato->max }}</td>
                                            <td>{{ $dato->min }}</td>
                                            <td>{{ $dato->amp }}</td>
                                            <td>{{ $dato->uc }}</td>
                                            <td>{{ $dato->uf }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@extends('layouts.web')
@section('title', 'Datos de Exportación')
@section('content')
    <div class="content">
        <div class="card">
            <div class="card-header header-elements-inline">
                <h5 class="card-title">Datos de Exportación</h5>
                <div class="header-elements">
                    <div class="list-icons">
                        <a class="list-icons-item" data-action="collapse"></a>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <h6>Información de la Zona de Manejo</h6>
                        <p><strong>Nombre:</strong> {{ $zona_manejo->nombre }}</p>
                        <p><strong>Periodo:</strong> {{ $inicio }} al {{ $fin }}</p>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12">
                        <h6>Datos de Mediciones</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Máxima Temperatura Suelo</th>
                                        <th>Mínima Temperatura Suelo</th>
                                        <th>Promedio Temperatura Suelo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rows as $row)
                                        <tr>
                                            <td>{{ $row['fecha'] }}</td>
                                            <td>{{ $row['max_temperatura_suelo'] }}</td>
                                            <td>{{ $row['min_temperatura_suelo'] }}</td>
                                            <td>{{ $row['avg_temperatura_suelo'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

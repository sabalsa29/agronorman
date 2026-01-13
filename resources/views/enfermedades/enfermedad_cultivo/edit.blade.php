@extends('layouts.web')
@section('title', 'Enfermedades')
@section('modelo', 'Editar')
@section('ruta_alternativa', route('enfermedades.cultivos.index', $especie_enfermedad->enfermedad_id))
@section('ruta_home', route('enfermedades.index'))
@section('title_ruta_interna', $section_name)
@section('styles')
    <!-- Theme JS files -->
    <script src="{{ url('global_assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/notifications/pnotify.min.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/form_inputs.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/selects/bootstrap_multiselect.js') }}"></script>

    <script src="{{ url('assets/js/app.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/form_multiselect.js') }}"></script>
    <!-- /theme JS files -->
@endsection
@section('content')
    <!-- Form inputs -->
    <div class="card">
        <div class="card-header header-elements-inline">
            <h5 class="card-title">{{ $section_name }}</h5>
            <div class="header-elements">
                <div class="list-icons">
                    <a class="list-icons-item" data-action="collapse"></a>
                    <a class="list-icons-item" data-action="reload"></a>
                    <a class="list-icons-item" data-action="remove"></a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <p class="mb-4">{{ $section_description }}</p>

            <form
                action="{{ route('enfermedades.cultivos.update', ['enfermedad' => $especie_enfermedad->enfermedad_id, 'tipoCultivo' => $especie_enfermedad->tipo_cultivo_id]) }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <fieldset class="mb-3">

                    <table class="table table-bordered text-center align-middle">
                        <thead>
                            <tr>
                                <th colspan="6" class="text-uppercase text-muted">

                                    <div class="form-group row">
                                        <div class="col-lg-12">
                                            <div class="form-group">
                                                <label class="col-form-label col-lg-12">Tipo de Cultivo</label>
                                                <input type="text" class="form-control" value="{{ $especie_enfermedad->tipoCultivo->nombre }}" readonly>
                                                <input type="hidden" name="tipo_cultivo_id" value="{{ $especie_enfermedad->tipo_cultivo_id }}">
                                            </div>
                                        </div>
                                    </div>
                                </th>
                            </tr>
                            <tr>
                                <th colspan="4" class="text-uppercase text-muted">Rangos de riesgo en enfermedad</th>
                                <th colspan="2" class="text-uppercase text-muted">Número de horas de alerta de riesgo
                                </th>
                            </tr>
                            <tr>
                                <th colspan="2">Temperatura</th>
                                <th colspan="2">Humedad</th>
                                <th rowspan="2">Alerta preventiva</th>
                                <th rowspan="2">Alerta de riesgo</th>
                            </tr>
                            <tr>
                                <th>Mínima</th>
                                <th>Máxima</th>
                                <th>Mínima</th>
                                <th>Máxima</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <input type="hidden" name="enfermedad_id"
                                        value="{{ $especie_enfermedad->enfermedad_id }}">
                                    <input type="number" name="riesgo_temperatura"
                                        value="{{ $especie_enfermedad->riesgo_temperatura }}" class="form-control">
                                </td>
                                <td>
                                    <input type="number" name="riesgo_temperatura_max"
                                        value="{{ $especie_enfermedad->riesgo_temperatura_max }}" class="form-control"
                                        value="">
                                </td>
                                <td>
                                    <input type="number" name="riesgo_humedad"
                                        value="{{ $especie_enfermedad->riesgo_humedad }}" class="form-control"
                                        value="">
                                </td>
                                <td>
                                    <input type="number" name="riesgo_humedad_max"
                                        value="{{ $especie_enfermedad->riesgo_humedad_max }}" class="form-control"
                                        value="">
                                </td>
                                <td>
                                    <input type="number" name="riesgo_medio"
                                        value="{{ $especie_enfermedad->riesgo_medio }}" class="form-control"
                                        value="">
                                </td>
                                <td>
                                    <input type="number" name="riesgo_mediciones"
                                        value="{{ $especie_enfermedad->riesgo_mediciones }}" class="form-control"
                                        value="">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>

                <div class="text-right">
                    <a href="{{ route('enfermedades.cultivos.index', $especie_enfermedad->enfermedad_id) }}" class="btn btn-secondary mr-2">
                        <i class="icon-cross2 mr-2"></i>Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="icon-paperplane mr-2"></i>Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- /form inputs -->
@endsection

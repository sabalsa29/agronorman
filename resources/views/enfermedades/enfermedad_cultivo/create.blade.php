@extends('layouts.web')
@section('title', 'Enfermedades')
@section('modelo', 'Agregar')
@section('ruta_home', route('enfermedades.index'))
@section('ruta_alternativa', route('enfermedades.cultivos.index', $enfermedad))
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

            <form action="{{ route('enfermedades.cultivos.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="enfermedad_id" value="{{ $enfermedad->id }}">

                <fieldset class="mb-3">
                    <!-- Selección múltiple de tipos de cultivo -->
                    <div class="form-group row">
                        <label class="col-form-label col-lg-2">Tipos de Cultivo <span class="text-danger">*</span></label>
                        <div class="col-lg-10">
                            <select class="form-control multiselect-select-all-filtering" name="cultivo_ids[]"
                                multiple="multiple" data-fouc>
                                @foreach ($cultivos as $res)
                                    <option value="{{ $res->id }}">{{ $res->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Valores de riesgo globales -->
                    <div class="row">
                        <div class="col-12">
                            <h6 class="font-weight-semibold">Rangos de riesgo en enfermedad (aplican a todos los tipos de
                                cultivo seleccionados)</h6>
                        </div>
                    </div>

                    <table class="table table-bordered text-center align-middle">
                        <thead>
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
                                    <input type="number" name="riesgo_temperatura" class="form-control" step="0.1"
                                        placeholder="Ej: 10">
                                </td>
                                <td>
                                    <input type="number" name="riesgo_temperatura_max" class="form-control" step="0.1"
                                        placeholder="Ej: 35">
                                </td>
                                <td>
                                    <input type="number" name="riesgo_humedad" class="form-control" step="0.1"
                                        placeholder="Ej: 60">
                                </td>
                                <td>
                                    <input type="number" name="riesgo_humedad_max" class="form-control" step="0.1"
                                        placeholder="Ej: 90">
                                </td>
                                <td>
                                    <input type="number" name="riesgo_medio" class="form-control" placeholder="Ej: 24">
                                </td>
                                <td>
                                    <input type="number" name="riesgo_mediciones" class="form-control"
                                        placeholder="Ej: 48">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </fieldset>

                <div class="text-right">
                    <a href="{{ route('enfermedades.cultivos.index', $enfermedad) }}" class="btn btn-secondary mr-2">
                        <i class="icon-cross2 mr-2"></i>Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="icon-paperplane mr-2"></i>Guardar Tipos de Cultivo
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- /form inputs -->
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Validación del formulario
            $('form').submit(function(e) {
                const cultivosSeleccionados = $('select[name="cultivo_ids[]"]').val();

                if (!cultivosSeleccionados || cultivosSeleccionados.length === 0) {
                    alert('Debe seleccionar al menos un tipo de cultivo.');
                    e.preventDefault();
                    return false;
                }

                // Verificar que no haya tipos de cultivo duplicados
                const cultivosUnicos = [...new Set(cultivosSeleccionados)];
                if (cultivosUnicos.length !== cultivosSeleccionados.length) {
                    alert('No puede seleccionar el mismo tipo de cultivo más de una vez.');
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
@endsection

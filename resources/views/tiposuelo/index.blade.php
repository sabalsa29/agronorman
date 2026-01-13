@extends('layouts.web')
@section('title', $section_name)
@section('modelo', 'Textura de suelo')
@section('ruta_home', route('textura-suelo.index'))
@section('styles')
    <!-- Theme JS files -->
    <script src="{{ url('global_assets/js/plugins/extensions/jquery_ui/interactions.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/styling/switchery.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/selects/select2.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/selects/bootstrap_multiselect.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/inputs/touchspin.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/uploaders/fileinput/plugins/purify.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/uploaders/fileinput/plugins/sortable.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/uploaders/fileinput/fileinput.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/extensions/contextmenu.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/form_inputs.js') }}"></script>

    <script src="{{ url('assets/js/app.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/table_elements.js') }}"></script>
    <!-- /theme JS files -->
@endsection
@section('content')
    <!-- Content area -->
    <div class="content">

        <!-- Table components -->
        <div class="card">
            <div class="card-header header-elements-inline">
                <h5 class="card-title">Configuración de parámetros para textura del suelo</h5>
            </div>

            <form action="{{ route('textura-suelo.update') }}" method="post">
                @csrf
                @method('POST')
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Textura/Niveles</th>
                                <th>Bajo</th>
                                <th>Óptimo Minimo</th>
                                <th>Óptimo Máximo</th>
                                <th>Alto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($suelos as $suelo)
                                <tr>
                                    <td>{{ $suelo->tipo_suelo }}</td>
                                    <td>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    < </span>
                                            </div>
                                            <input type="text" class="form-control" data-parsley-required
                                                name="bajo[<?php echo $suelo->id; ?>]" value="<?php echo $suelo->bajo; ?>">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="col-lg-12">
                                            <div class="row align-items-end">
                                                <div class="col-12">
                                                    <input type="text" class="form-control col-12" data-parsley-required
                                                        name="optimo_min[<?php echo $suelo->id; ?>]" value="<?php echo $suelo->optimo_min; ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="col-lg-12">
                                            <div class="row align-items-end">
                                                <div class="col-12">
                                                    <input type="text" class="form-control col-12" data-parsley-required
                                                        name="optimo_max[<?php echo $suelo->id; ?>]" value="<?php echo $suelo->optimo_max; ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">></span>
                                            </div>
                                            <input type="text" class="form-control" data-parsley-required
                                                name="alto[<?php echo $suelo->id; ?>]" value="<?php echo $suelo->alto; ?>">
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="text-right px-3 py-3">
                    <button type="submit" class="btn btn-primary">Guardar <i class="icon-paperplane ml-2"></i></button>
                </div>
            </form>
        </div>
        <!-- /table components -->

    </div>
    <!-- /content area -->
@endsection

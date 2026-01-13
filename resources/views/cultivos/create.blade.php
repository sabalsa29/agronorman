@extends('layouts.web')
@section('title', $section_name)
@section('modelo', 'Agregar')
@section('ruta_home', route('cultivos.index'))
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

            <form action="{{ route('cultivos.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <fieldset class="mb-3">

                    <div class="form-group row">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-4">
                                    <label class="col-form-label col-lg-12">Nombre <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="nombre" class="form-control" placeholder="Nombre">
                                </div>

                                <div class="col-4">
                                    <label class="col-form-label col-lg-12">Tipo de Cultivo / Variedad / Biotipo <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="descripcion" class="form-control"
                                        placeholder="Tipo de Cultivo / Variedad / Biotipo">
                                </div>

                                <div class="col-4">
                                    <label class="col-form-label col-lg-12">Ciclo de vida <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control" name="tipo_vida">
                                        <option value="">Elige una opción</option>
                                        <option value="1">Cíclica</option>
                                        <option value="2">Perene</option>
                                    </select>
                                </div>

                            </div>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="mb-3">
                    <legend class="text-uppercase font-size-sm font-weight-bold">Parámetros para unidades calor</legend>

                    <div class="form-group row">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-6">
                                    <label class="col-form-label col-lg-12">Temp. base <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="temp_base_calor" class="form-control"
                                        placeholder="Temp. base">
                                </div>
                                <div class="col-6"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-lg-6">
                                    <label class="col-form-label col-lg-2">Imagen:</label>
                                    <div class="col-lg-12">
                                        <input type="file" name="imagen" class="form-control-uniform" data-fouc>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <label class="col-form-label col-lg-2">Icono:</label>
                                    <div class="col-lg-12">
                                        <input type="file" name="icono" class="form-control-uniform" data-fouc>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </fieldset>

                <div class="text-right">
                    <button type="submit" class="btn btn-primary">Agregar <i class="icon-paperplane ml-2"></i></button>
                </div>
            </form>
        </div>
    </div>
    <!-- /form inputs -->
@endsection

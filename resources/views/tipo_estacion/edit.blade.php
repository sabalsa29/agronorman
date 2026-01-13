@extends('layouts.web')
@section('title', $section_name)
@section('modelo', 'Actualizar')
@section('ruta_home', route('tipo_estacion.index'))
@section('styles')
    <!-- Theme JS files -->
    <script src="{{ url('global_assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/styling/switchery.min.js') }}"></script>

    <script src="{{ url('assets/js/app.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/form_inputs.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/table_elements.js') }}"></script>
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

            <form action="{{ route('tipo_estacion.update', $tipoEstacion) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <fieldset class="mb-3">

                    <div class="form-group row">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-6">
                                    <label class="col-form-label col-lg-2">Nombre</label>
                                    <div class="col-lg-12">
                                        <input type="text" name="nombre" value="{{ $tipoEstacion->nombre }}"
                                            class="form-control">
                                    </div>
                                </div>

                                <div class="col-6">
                                    <label class="col-form-label col-lg-12">Tipo Nasa <span
                                            class="text-danger">*</span></label>
                                    <div class="col-lg-12">
                                        <select class="form-control" name="tipo_nasa">
                                            <option value="">Seleccione</option>
                                            <option value="1" @selected($tipoEstacion->tipo_nasa == 1)>Si</option>
                                            <option value="0" @selected($tipoEstacion->tipo_nasa == 0)>No</option>
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-form-label col-lg-2">Estatus</label>
                        <div class="col-lg-10">
                            <div class="form-check form-check-inline form-check-switchery">
                                <label class="form-check-label">
                                    <!-- Enviará 0 si no está activado -->
                                    <input type="hidden" name="status" @checked($tipoEstacion->status == 0) value="0">
                                    <!-- Enviará 1 si está activado -->
                                    <input type="checkbox" name="status" @checked($tipoEstacion->status == 1) value="1"
                                        class="form-input-switchery">
                                </label>
                            </div>
                        </div>
                    </div>

                </fieldset>

                <div class="text-right">
                    <button type="submit" class="btn btn-primary">Actualizar <i class="icon-paperplane ml-2"></i></button>
                </div>
            </form>
        </div>
    </div>
    <!-- /form inputs -->
@endsection

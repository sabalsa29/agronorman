@extends('layouts.web')
@section('title', $section_name)
@section('modelo', 'Agregar')
@section('ruta_home', route('estaciones.index'))
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

            <form action="{{ route('estaciones.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <fieldset class="mb-3">

                    <div class="form-group row">
                        <div class="col-lg-12">
                            <div class="row">

                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="col-form-label col-lg-12">Estatus <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control" name="status">
                                            @foreach ($estatusOptions as $value => $label)
                                                <option value="{{ $value }}"
                                                    {{ old('status') == $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="col-form-label col-lg-12">Fabricantes <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control" name="fabricante_id">
                                            @foreach ($fabricantes as $res)
                                                <option value="{{ $res->id }}">{{ $res->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-lg-12">
                            <div class="row">

                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="col-form-label col-lg-12">Almacen <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control" name="almacen_id">
                                            @foreach ($almacenes as $res)
                                                <option value="{{ $res->id }}">{{ $res->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <label class="col-form-label col-lg-12">Caracteristicas <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="caracteristicas" class="form-control"
                                        placeholder="Caracteristicas">
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-6">
                                    <label class="col-form-label col-lg-12">ID <span class="text-danger">*</span></label>
                                    <input type="text" name="uuid" class="form-control" placeholder="UUID">
                                </div>

                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="col-form-label col-lg-12">Tipo <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control" name="tipo_estacion_id">
                                            @foreach ($tipos as $res)
                                                <option value="{{ $res->id }}">{{ $res->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-6">
                            <label class="col-form-label col-lg-12">Celular <span class="text-danger">*</span></label>
                            <input type="text" name="celular" class="form-control" placeholder="Celular">
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="col-form-label col-lg-12">Variables <span class="text-danger">*</span></label>
                                <select class="form-control multiselect-select-all-filtering" name="variables_medicion_id[]"
                                    multiple="multiple" data-fouc>
                                    @foreach ($variables as $res)
                                        <option value="{{ $res->id }}">{{ $res->nombre }}</option>
                                    @endforeach
                                </select>
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

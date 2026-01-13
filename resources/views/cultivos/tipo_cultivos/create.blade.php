@extends('layouts.web')
@section('title', $section_name)
@section('modelo', 'Agregar')
@section('ruta_home', route('tipo_cultivos.index', $cultivo_id))
@section('styles')
    <!-- Theme JS files -->
    <script src="{{ url('global_assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/styling/switchery.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/notifications/pnotify.min.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/form_inputs.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/selects/bootstrap_multiselect.js') }}"></script>

    <script src="{{ url('assets/js/app.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/form_multiselect.js') }}"></script>
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

            <form action="{{ route('tipo_cultivos.store', $cultivo_id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <fieldset class="mb-3">

                    <div class="form-group row">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-6">
                                    <label class="col-form-label col-lg-12">Nombre <span
                                            class="text-danger">*</span></label>
                                    <input type="hidden" name="cultivo_id" value="{{ $cultivo_id }}">
                                    <input type="text" name="nombre" class="form-control" placeholder="Nombre">
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="col-form-label col-lg-12">Etapas Fenologicas <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control multiselect-select-all-filtering"
                                            name="etapa_fenologica_id[]" multiple="multiple" data-fouc>
                                            @foreach ($etapas_fenologicas as $res)
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
                                        <label class="col-form-label col-lg-12">Plagas <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control multiselect-select-all-filtering" name="plaga_id[]"
                                            multiple="multiple" data-fouc>
                                            @foreach ($plagas as $res)
                                                <option value="{{ $res->id }}">{{ $res->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="col-form-label col-lg-12">Enfermedades <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control multiselect-select-all-filtering" name="enfermedad_id[]"
                                            multiple="multiple" data-fouc>
                                            @foreach ($enfermedades as $res)
                                                <option value="{{ $res->id }}">{{ $res->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-form-label col-lg-1">Estatus</label>
                        <div class="col-lg-12">
                            <div class="form-check form-check-inline form-check-switchery">
                                <label class="form-check-label">
                                    <!-- Enviará 0 si no está activado -->
                                    <input type="hidden" name="status" value="0">
                                    <!-- Enviará 1 si está activado -->
                                    <input type="checkbox" name="status" value="1" class="form-input-switchery"
                                        checked data-fouc>
                                </label>
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

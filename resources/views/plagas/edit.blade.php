@extends('layouts.web')
@section('title', $section_name)
@section('modelo', 'Editar')
@section('ruta_home', route('plaga.index'))
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

            <form action="{{ route('plaga.update', $plaga) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <fieldset class="mb-3">

                    <div class="form-group row">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-6">
                                    <label class="col-form-label col-lg-12">Nombre <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="nombre" value="{{ $plaga->nombre }}" class="form-control"
                                        placeholder=".col-3">
                                </div>

                                <div class="col-6">
                                    <label class="col-form-label col-lg-12">Descripción <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="descripcion" value="{{ $plaga->descripcion }}"
                                        class="form-control" placeholder=".col-4">
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label class="col-form-label col-lg-12">Cultivo <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control multiselect-select-all-filtering" name="cultivo_id[]"
                                            multiple="multiple" data-fouc>
                                            @foreach ($cultivos as $res)
                                                <option value="{{ $res->id }}" @selected(in_array($res->id, $selectedCultivos))>
                                                    {{ $res->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-6">
                                    <label class="col-form-label col-lg-12">Unidades calor por ciclo <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="unidades_calor_ciclo"
                                        value="{{ $plaga->unidades_calor_ciclo }}" class="form-control"
                                        placeholder=".col-4">
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-lg-6">
                            <div class="row">
                                <div class="col-6">
                                    <label class="col-form-label col-lg-12">Umbral minimo</label>
                                    <input type="text" name="umbral_min" value="{{ $plaga->umbral_min }}"
                                        class="form-control" placeholder=".col-3">
                                </div>

                                <div class="col-6">
                                    <label class="col-form-label col-lg-12">Umbral máximo</label>
                                    <input type="text" name="umbral_max" value="{{ $plaga->umbral_max }}"
                                        class="form-control" placeholder=".col-4">
                                </div>

                            </div>
                        </div>
                        <div class="col-lg-6"></div>
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

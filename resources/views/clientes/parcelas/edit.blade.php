@extends('layouts.web')
@section('title', $section_name)
@section('modelo', 'Agregar')
@section('ruta_home', route('parcelas.index', ['id' => $cliente_id]))
@section('styles')
    <!-- Theme JS files -->
    <script src="{{ url('global_assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/styling/switchery.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/selects/select2.min.js') }}"></script>

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

            <form action="{{ route('parcelas.update', ['id' => $cliente_id, $parcela]) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <fieldset class="mb-3">

                    <div class="form-group row">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-6">
                                    <label class="col-form-label col-lg-12">Nombre <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="nombre" value="{{ $parcela->nombre }}" class="form-control"
                                        placeholder="Nombre">
                                </div>
                                <div class="col-6">
                                    <label class="col-form-label col-lg-12">Superficie (hectáreas) <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="superficie" value="{{ $parcela->superficie }}"
                                        class="form-control" placeholder="Superficie (hectáreas)">
                                </div>
                                <div class="col-6">
                                    <label class="col-form-label col-lg-12">Latitud <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="lat" value="{{ $parcela->lat }}" class="form-control"
                                        placeholder="Latitud">
                                </div>
                                <div class="col-6">
                                    <label class="col-form-label col-lg-12">Longitud <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="lon" value="{{ $parcela->lon }}" class="form-control"
                                        placeholder="Longitud">
                                </div>

                                <div class="col-6">
                                    <label class="col-form-label col-lg-12">
                                        Grupo <span class="text-danger">*</span>
                                    </label>
                                    <div  class="form-control">
                                        <select name="grupo_id[]" multiple id="grupo_id" class="form-control select2">
                                            @php
                                                $selectedGrupos = collect(old('grupo_id', $gruposAsignados ?? []))
                                                    ->map(fn($v) => (string)$v)
                                                    ->toArray();
                                            @endphp

                                            @foreach ($gruposDisponibles as $grupo)
                                                <option value="{{ $grupo['id'] }}"
                                                    {{ in_array((string)$grupo['id'], $selectedGrupos, true) ? 'selected' : '' }}>
                                                    {{ $grupo['nombre'] }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <span class="form-text text-muted">
                                            <i class="icon-info22 mr-1"></i>
                                            Seleccione al menos un grupo
                                        </span>
                                    </div>
                                </div>

                                <div class="col-12 mt-2">
                                    <div class="form-check form-check-inline form-check-switchery">
                                        <label class="form-check-label">
                                            <!-- Enviará 0 si no está activado -->
                                            <input type="hidden" name="status" @checked($parcela->status == 0)
                                                value="0">
                                            <!-- Enviará 1 si está activado -->
                                            <input type="checkbox" name="status" value="1"
                                                class="form-input-switchery" @checked($parcela->status == 1) data-fouc>
                                            Status
                                        </label>
                                    </div>
                                </div>
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
@section('scripts')
    <script>
        $(document).ready(function() {

            // Inicializar Select2
            $('.select2').select2({
                allowClear: true
            });
        });
    </script>
@endsection

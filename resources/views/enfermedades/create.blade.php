@extends('layouts.web')
@section('title', $section_name)
@section('modelo', 'Agregar')
@section('ruta_home', route('enfermedades.index'))
@section('styles')
    <!-- Theme JS files -->
    <script src="{{ url('global_assets/js/plugins/forms/styling/uniform.min.js') }}"></script>

    <script src="{{ url('assets/js/app.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/form_inputs.js') }}"></script>
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

            <form action="{{ route('enfermedades.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <fieldset class="mb-3">

                    <div class="form-group row">
                        <label class="col-form-label col-lg-2">Nombre <span class="text-danger">*</span></label>
                        <div class="col-lg-10">
                            <input type="text" name="nombre" value="{{ old('nombre') }}"
                                class="form-control @error('nombre') is-invalid @enderror" required>
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-form-label col-lg-2">Estatus</label>
                        <div class="col-lg-10">
                            <select name="status" class="form-control @error('status') is-invalid @enderror">
                                <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ old('status') == 0 ? 'selected' : '' }}>Inactivo</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </fieldset>

                <div class="text-right">
                    <a href="{{ route('enfermedades.index') }}" class="btn btn-secondary mr-2">
                        <i class="icon-cross2 mr-2"></i>Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="icon-paperplane mr-2"></i>Crear Enfermedad
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- /form inputs -->
@endsection

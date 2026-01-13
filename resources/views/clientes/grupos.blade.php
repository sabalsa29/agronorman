@extends('layouts.web')
@section('title', $section_name)
@section('ruta_home', route('clientes.index'))
@section('content')
    <!-- Mensajes de éxito y error -->
    @if (session('success'))
        <div class="alert alert-success alert-styled-left alert-arrow-left alert-dismissible">
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            <span class="font-weight-semibold">{{ session('success') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-styled-left alert-arrow-left alert-dismissible">
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            <span class="font-weight-semibold">{{ session('error') }}</span>
        </div>
    @endif

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

            <form action="{{ route('clientes.grupos.store', $cliente) }}" method="POST">
                @csrf
                <fieldset class="mb-3">
                    <div class="form-group">
                        <label class="font-weight-semibold">
                            <i class="icon-collaboration mr-2"></i>
                            Seleccionar Grupos
                        </label>
                        <select name="grupos[]" id="grupos" class="form-control select-multiple" multiple="multiple"
                            data-placeholder="Selecciona uno o más grupos">
                            @foreach ($gruposDisponibles as $grupo)
                                <option value="{{ $grupo['id'] }}"
                                    {{ in_array($grupo['id'], $gruposAsignados) ? 'selected' : '' }}>
                                    {{ $grupo['nombre'] }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">
                            <i class="icon-info22 mr-1"></i>
                            Solo puedes seleccionar grupos padre (raíz). Al asignar un grupo padre, el cliente tendrá acceso
                            a todas las zonas de manejo de ese grupo y sus subgrupos descendientes.
                        </small>
                    </div>
                </fieldset>

                <div class="text-right">
                    <a href="{{ route('clientes.index') }}" class="btn btn-light">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        Guardar Grupos <i class="icon-paperplane ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- /form inputs -->
@endsection
@section('scripts')
    <!-- Theme JS files -->
    <script src="{{ url('global_assets/js/plugins/forms/selects/select2.min.js') }}"></script>
    <script src="{{ url('assets/js/app.js') }}"></script>
    <script>
        $(document).ready(function() {
            // Inicializar Select2 para selección múltiple
            $('#grupos').select2({
                placeholder: 'Selecciona uno o más grupos',
                allowClear: true,
                width: '100%'
            });
        });
    </script>
    <!-- /theme JS files -->
@endsection

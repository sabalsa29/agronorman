@extends('layouts.web')
@section('title', $section_name)
@section('modelo', 'Agregar')
@section('ruta_home', route('grupos.index'))

@section('styles')
    <!-- Theme JS files -->
    <script src="{{ url('global_assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/styling/switchery.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/selects/select2.min.js') }}"></script>

    <script src="{{ url('assets/js/app.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/form_inputs.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/table_elements.js') }}"></script>
@endsection

@section('content')
    <div class="card">
        <div class="card-header header-elements-inline d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">{{ $section_name }}</h5>
            <div class="header-elements ml-auto">
                <div class="list-icons">
                    <a class="list-icons-item" data-action="collapse"></a>
                    <a class="list-icons-item" data-action="reload"></a>
                    <a class="list-icons-item" data-action="remove"></a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <p class="mb-4">{{ $section_description }}</p>

            <form action="{{ route('zonas.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <fieldset class="mb-3">

                    {{-- Grupo --}}
                    <div class="form-group row">
                        <label class="col-form-label col-lg-2">
                            <i class="icon-collaboration mr-1"></i>
                            Grupo <span class="text-danger">*</span>
                        </label>
                        <div class="col-lg-10">
                            <select name="grupo_id" id="grupo_id" class="form-control select2">
                                <option value="">(Sin grupo)</option>
                                @foreach ($gruposDisponibles as $grupo)
                                    <option value="{{ $grupo['id'] }}"
                                        {{ old('grupo_id', $grupoPadreId ?? null) == $grupo['id'] ? 'selected' : '' }}>
                                        {{ $grupo['nombre'] }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="form-text text-muted">
                                <i class="icon-info22 mr-1"></i>
                                Seleccione un grupo
                            </span>
                        </div>
                    </div>

                    {{-- Predio (antes "parcela") --}}
                    <div class="form-group row">
                        <label class="col-form-label col-lg-2">
                            <i class="icon-map5"></i> Predio <span class="text-danger">*</span>
                        </label>
                        <div class="col-lg-10">
                            <select name="predio_id" id="predio_id" class="form-control select2">
                                <option value="">(Seleccione un predio)</option>
                                @foreach ($parcelas as $parcela)
                                    <option value="{{ $parcela['id'] }}"
                                        {{ old('predio_id') == $parcela['id'] ? 'selected' : '' }}>
                                        {{ $parcela['nombre'] }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="form-text text-muted">
                                <i class="icon-info22 mr-1"></i>
                                Seleccione un predio para cargar sus zonas
                            </span>
                        </div>
                    </div>

                    {{-- Zonas (dependen del predio) --}}
                    <div class="form-group row">
                        <label class="col-form-label col-lg-2">
                            <i class="icon-grid6"></i> Zonas <span class="text-danger">*</span>
                        </label>
                        <div class="col-lg-10">
                            <select name="zona_manejo_ids[]" id="zona_manejo_ids"
                                class="form-control select2" multiple="multiple">
                                <option value="">(Sin selección)</option>
                                {{-- Opcional: si quieres precargar cuando ya existe old(), puedes renderizar aquí desde backend --}}
                            </select>
                            <span class="form-text text-muted">
                                <i class="icon-info22 mr-1"></i>
                                Seleccione al menos una zona para asignarla al grupo
                            </span>
                        </div>
                    </div>

                    {{-- Estatus --}}
                    <div class="form-group row">
                        <label class="col-form-label col-lg-2">Estatus</label>
                        <div class="col-lg-10">
                            <div class="form-check form-check-inline form-check-switchery">
                                <label class="form-check-label">
                                    <input type="hidden" name="status" value="0">
                                    <input type="checkbox" name="status" value="1" class="form-input-switchery"
                                        {{ old('status', 1) ? 'checked' : '' }} data-fouc>
                                </label>
                            </div>
                        </div>
                    </div>

                </fieldset>

                <div class="text-right">
                    <button type="submit" class="btn btn-primary">
                        Agregar <i class="icon-paperplane ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {

            // Inicializar Select2
            $('.select2').select2({
                allowClear: true
            });

            // Placeholders específicos
            $('#grupo_id').select2({ placeholder: 'Seleccione un grupo', allowClear: true });
            $('#predio_id').select2({ placeholder: 'Seleccione un predio', allowClear: true });
            $('#zona_manejo_ids').select2({ placeholder: 'Seleccione una o más zonas', allowClear: true });

            // Cargar zonas según predio
            $('#predio_id').on('change', function() {
                console.log('Predio cambiado');
                const predioId = $(this).val();
                const $zonas = $('#zona_manejo_ids');

                // limpiar select
                $zonas.empty().trigger('change');

                if (!predioId) return;

                $.get("{{ url('/predios') }}/" + predioId + "/zonas", function(data) {
                    data.forEach(z => {
                        $zonas.append(new Option(z.nombre, z.id, false, false));
                    });
                    $zonas.trigger('change');
                });
            });

            // Si vienes con old('predio_id'), dispara carga automática
            const predioOld = "{{ old('predio_id') }}";
            if (predioOld) {
                $('#predio_id').val(predioOld).trigger('change');
            }
        });
    </script>
@endsection

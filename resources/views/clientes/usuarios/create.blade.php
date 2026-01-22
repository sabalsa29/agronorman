@extends('layouts.web')
@section('title', $section_name)
@section('modelo', 'Agregar')
@section('ruta_home', route('usuarios.index', ['id' => $cliente_id]))
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

            <form action="{{ route('usuarios.store', ['id' => $cliente_id]) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <fieldset class="mb-3">

                    <div class="form-group row">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-3">
                                    <label class="col-form-label col-lg-12">Nombre <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="nombre" class="form-control" placeholder="Nombre">
                                    <input type="hidden" name="cliente_id" value="{{ $cliente_id }}">
                                </div>
                                <div class="col-3">
                                    <label class="col-form-label col-lg-12">Correo <span
                                            class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" placeholder="Correo">
                                </div>
                                <div class="col-3">
                                    <label class="col-form-label col-lg-12">Contraseña <span
                                            class="text-danger">*</span></label>
                                    <input type="password" name="password" class="form-control" placeholder="Contraseña">
                                </div>
                                <div class="col-3">
                                    <label class="col-form-label col-lg-12">Rol <span class="text-danger">*</span></label>
                                    <select name="role_id" class="form-control" required>
                                        <option value="">Seleccione un rol</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}">{{ $role->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('role_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                           
                            <div class="row mt-3">
                                <div class="col-6">
                                    <label class="col-form-label col-lg-12">Grupo <span class="text-danger">*</span></label>
                                    <select name="grupo_id[]" multiple id="grupo_id" class="form-control select2">
                                        <option value="">Sin grupo (solo zonas asignadas directamente)</option>
                                         @foreach ($gruposDisponibles as $grupo)
                                            <option value="{{ $grupo['id'] }}"
                                                {{ old('grupo_id', $grupoPadreId ?? null) == $grupo['id'] ? 'selected' : '' }}>
                                                {{ $grupo['nombre'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">
                                        Asignar grupos para permite acceso jerárquico a todos los predios y zonas dentro de esos grupos.
                                    </small>
                                    @error('grupo_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                   {{-- =========================
                        Sección: Asignación manual (Grupo → Predios/Zonas)
                        ========================= --}}
                    <div class="card border-left-3 border-left-primary rounded-left-0 mb-3">
                        <div class="card-header bg-light d-flex align-items-center">
                            <h6 class="card-title mb-0">
                                <i class="icon-link mr-2"></i> Asignación manual
                            </h6>
                            <span class="badge badge-primary ml-2">Accesos</span>
                        </div>

                        <div class="card-body">

                            {{-- Grupo, seleccionar solo un grupo  --}}
                            <div class="form-group row">
                                <label class="col-form-label">
                                    Grupo
                                </label>

                                <div class="col-lg-4">
                                    <select onchange="cargarPredios()" name="grupo_manual_id" id="grupo_manual_id" class="form-control">
                                        @foreach ($gruposDisponibles as $grupo)
                                            <option value="{{ $grupo['id'] }}"
                                                {{ old('grupo_manual_id', $grupoPadreId ?? null) == $grupo['id'] ? 'selected' : '' }}>
                                                {{ $grupo['nombre'] }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <span class="form-text text-muted">
                                        <i class="icon-info22 mr-1"></i>
                                        Seleccione un grupo para asignar predios o zonas de manejo.
                                    </span>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-6">
                                        <label class="col-form-label col-lg-12">Predios</label>
                                        <select name="predio_ids[]" multiple class="form-control select2">
                                            @foreach ($prediosDisponibles as $predio)
                                                <option value="{{ $predio['id'] }}">{{ $predio['nombre'] }}</option>
                                            @endforeach
                                        </select>
                                        <span class="form-text text-muted">
                                            <i class="icon-info22 mr-1"></i>
                                            Seleccione los predios a asignar al usuario.
                                        </span>

                                    </div>
                                    </div>
                            </div>

                            {{-- Aquí puedes agregar los selects/inputs de Predio y Zona de manejo --}}
                            {{-- Ej: predio_id[], zona_manejo_id[] --}}

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
@section('scripts')
    <!-- Theme JS files -->
    <script src="{{ url('global_assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/styling/switchery.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/selects/select2.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/selects/bootstrap_multiselect.js') }}"></script>
    <script src="{{ url('assets/js/app.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/form_inputs.js') }}"></script>
    <script>
        // Cargar table_elements.js solo si las dependencias están disponibles
        document.addEventListener('DOMContentLoaded', function() {

            // Inicializar Select2
            $('.select2').select2({
                allowClear: true
            });

             $('#grupo_manual_id').select2({
                width: '100%',
                placeholder: 'Buscar grupo...',
                allowClear: true
            });

            if (typeof $ !== 'undefined' && typeof Switchery !== 'undefined') {
                var script = document.createElement('script');
                script.src = "{{ url('global_assets/js/demo_pages/table_elements.js') }}";
                document.body.appendChild(script);
            }

           
        });

         function cargarPredios() {
                var grupoId = document.getElementById('grupo_manual_id').value;
                // Aquí puedes agregar la lógica para cargar los predios asociados al grupo seleccionado
                console.log('Cargando predios para el grupo ID:', grupoId);
            }
    </script>
    <!-- /theme JS files -->
@endsection

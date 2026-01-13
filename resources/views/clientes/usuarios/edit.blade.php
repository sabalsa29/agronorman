@extends('layouts.web')
@section('title', $section_name)
@section('modelo', 'Actualizar')
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

            <form action="{{ route('usuarios.update', ['id' => $cliente_id, $usuario]) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <fieldset class="mb-3">

                    <div class="form-group row">
                        <div class="col-lg-12">
                            <div class="row">
                                <div class="col-3">
                                    <label class="col-form-label col-lg-12">Nombre <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="nombre" value="{{ $usuario->nombre }}" class="form-control"
                                        placeholder="Nombre">
                                    <input type="hidden" name="cliente_id" value="{{ $cliente_id }}">
                                </div>
                                <div class="col-3">
                                    <label class="col-form-label col-lg-12">Correo <span
                                            class="text-danger">*</span></label>
                                    <input type="email" name="email" value="{{ $usuario->email }}" class="form-control"
                                        placeholder="Correo">
                                </div>
                                <div class="col-3">
                                    <label class="col-form-label col-lg-12">Contraseña</label>
                                    <input type="password" name="password" class="form-control"
                                        placeholder="Dejar vacío para mantener la actual">
                                    <small class="form-text text-muted">Dejar vacío si no desea cambiar la
                                        contraseña</small>
                                </div>
                                <div class="col-3">
                                    <label class="col-form-label col-lg-12">Rol <span class="text-danger">*</span></label>
                                    <select name="role_id" class="form-control" required>
                                        <option value="">Seleccione un rol</option>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}"
                                                {{ $usuario->role_id == $role->id ? 'selected' : '' }}>
                                                {{ $role->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('role_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-6">
                                    <label class="col-form-label col-lg-12">Grupo</label>
                                    <select name="grupo_id" class="form-control">
                                        <option value="">Sin grupo (solo zonas asignadas directamente)</option>
                                        @foreach ($gruposDisponibles as $grupo)
                                            <option value="{{ $grupo['id'] }}"
                                                {{ $usuario->grupo_id == $grupo['id'] ? 'selected' : '' }}>
                                                {{ $grupo['nombre'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">
                                        Asignar un grupo permite acceso jerárquico a todas las zonas del grupo y sus
                                        descendientes
                                    </small>
                                    @error('grupo_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
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
            if (typeof $ !== 'undefined' && typeof Switchery !== 'undefined') {
                var script = document.createElement('script');
                script.src = "{{ url('global_assets/js/demo_pages/table_elements.js') }}";
                document.body.appendChild(script);
            }
        });
    </script>
    <!-- /theme JS files -->
@endsection

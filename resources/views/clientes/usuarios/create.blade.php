@extends('layouts.web')
@section('title', $section_name)
@section('modelo', 'Agregar')
@section('ruta_home', route('usuarios.index', ['id' => $cliente_id]))
@section('content')
    <!-- Form inputs -->
    <style>
         /* Árbol simple */
        .tree ul { list-style: none; margin: 0; padding-left: 18px; }
        .tree li { margin: 6px 0; position: relative; }
        .tree li::before {
            content: "";
            position: absolute;
            left: -10px;
            top: 10px;
            width: 10px;
            height: 1px;
            background: #d9d9d9;
        }
        .tree .node {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border: 1px solid #eee;
            border-radius: 6px;
            background: #fafafa;
        }
        .tree .node .actions { margin-left: 8px; }
    </style>
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
                            <div class="form-group row">
                                <div class="col-lg-10">
                                    <div class="row">
                                        <!-- Grupo -->
                                        <div class="col-md-4">
                                            <label class="col-form-label">Grupo</label>
                                            <select onchange="cargarPredios()"
                                                    name="grupo_manual_id"
                                                    id="grupo_manual_id"
                                                    class="form-control select2 w-100">
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

                                        <!-- Predios -->
                                        <div class="col-md-4">
                                            <label class="col-form-label">Predios</label>
                                            <select onchange="cargarZonas()"
                                                    name="predio_ids[]"
                                                    id="predio_ids"
                                                    multiple
                                                    class="form-control select2 w-100"
                                                    style="min-height: 38px;">
                                            </select>

                                            <span class="form-text text-muted">
                                                <i class="icon-info22 mr-1"></i>
                                                Seleccione los predios a asignar al usuario.
                                            </span>
                                        </div>

                                        <!-- Zonas -->
                                        <div class="col-md-4">
                                            <label class="col-form-label">Zonas</label>
                                            <select name="zona_ids[]"
                                                    id="zona_ids"
                                                    multiple
                                                    class="form-control select2 w-100"
                                                    style="min-height: 38px;">
                                            </select>

                                            <span class="form-text text-muted">
                                                <i class="icon-info22 mr-1"></i>
                                                Seleccione las zonas a asignar al usuario.
                                            </span>
                                        </div>

                                    </div>

                                    <!-- Botón -->
                                    <div class="row mt-3">
                                        <div class="col-12 text-right">
                                            <button type="button"
                                                    class="btn btn-success"
                                                    onclick="agregarZonaPredio()">
                                                <i class="icon-plus-circle2 mr-1"></i> Agregar
                                            </button>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- =========================
                        RESUMEN (árbol) - al final del formulario
                        ========================= -->
                    <div class="card mt-3" id="resumen_asignaciones_card" style="display:none;">
                        <div class="card-header header-elements-inline">
                            <h6 class="card-title mb-0">
                                <i class="icon-tree6 mr-2"></i> Resumen de asignaciones (en memoria)
                            </h6>
                            <div class="header-elements">
                                <div class="list-icons">
                                    <a class="list-icons-item" data-action="collapse"></a>
                                </div>
                            </div>
                        </div>

                        <div class="card-body">
                            <div id="asignaciones_tree" class="pl-2"></div>

                            <!-- Opcional: si después quieres enviar esto al backend -->
                            <input type="hidden" name="asignaciones_cache" id="asignaciones_cache" value="">
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
                //Concexion ajax para obtener los predios del grupo
                $.ajax({
                    url: '/grupos/' + grupoId + '/predios',
                    method: 'GET',
                    success: function(data) {
                        var prediosSelect = $('select[name="predio_ids[]"]');
                        prediosSelect.empty();
                        $.each(data, function(index, predio) {
                            prediosSelect.append($('<option>', {
                                value: predio.id,
                                text: predio.nombre
                            }));
                        });
                    },
                    error: function(xhr) {
                        console.error('Error al cargar los predios:', xhr);
                    }
                });
                //limpiar zonas
                $('select[name="zona_ids[]"]').empty();
                // Aquí puedes agregar la lógica para cargar los predios asociados al grupo seleccionado
                console.log('Cargando predios para el grupo ID:', grupoId);
            }

            function cargarZonas() {
                var predioIds = $('select[name="predio_ids[]"]').val();

                console.log('Predio IDs seleccionados:', predioIds);
                //Concexion ajax para obtener las zonas de los predios
                $.ajax({
                    url: '/predios/' + predioIds + '/zonas',
                    method: 'GET',
                    data: {
                        predio_ids: predioIds,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(data) {
                        var zonasSelect = $('select[name="zona_ids[]"]');
                        zonasSelect.empty();
                        $.each(data, function(index, zona) {
                            const opt = new Option(zona.predio.nombre + ' — ' + zona.nombre, zona.id, false, false);
                            opt.dataset.predioId = zona.predio_id;
                            zonasSelect.append(opt);
                        });
                    },
                    error: function(xhr) {
                        console.error('Error al cargar las zonas:', xhr);
                    }
                });
                // Aquí puedes agregar la lógica para cargar las zonas asociadas a los predios seleccionados
                console.log('Cargando zonas para los predios IDs:', predioIds);
            }

            /**
             * Cache en memoria:
             * cache[groupId] = { id, nombre, predios: { [predioId]: {id,nombre, zonas:{[zonaId]:{id,nombre}} } } }
             */
            const asignacionesCache = {};

            // Helper: leer texto seleccionado
            function getSelectedText($select) {
                const data = $select.select2('data') || [];
                return data.map(x => ({ id: String(x.id), text: x.text }));
            }

            // IMPORTANT:
            // Para poder asociar cada zona a su predio cuando hay múltiples predios seleccionados,
            // al poblar el select de zonas agrega data-predio-id en cada <option>.
            //
            // Ejemplo dentro de tu cargarZonas():
            // const opt = new Option(z.nombre + ' — ' + z.predio_nombre, z.id, false, false);
            // opt.dataset.predioId = z.predio_id;
            // $('#zona_ids').append(opt).trigger('change');

            function agregarZonaPredio() {
                const $grupo = $('#grupo_manual_id');
                const $predios = $('#predio_ids');
                const $zonas = $('#zona_ids');

                const grupoId = $grupo.val();
                const predioIds = $predios.val() || [];

                // Obligatorios: grupo + al menos 1 predio
                if (!grupoId) {
                    Swal.fire({ icon: 'warning', title: 'Falta Grupo', text: 'Seleccione un grupo.' });
                    return;
                }
                if (predioIds.length === 0) {
                    Swal.fire({ icon: 'warning', title: 'Faltan Predios', text: 'Seleccione al menos un predio.' });
                    return;
                }

                const grupoNombre = $grupo.find('option:selected').text().trim();

                // Inicializa grupo
                if (!asignacionesCache[grupoId]) {
                    asignacionesCache[grupoId] = { id: String(grupoId), nombre: grupoNombre, predios: {} };
                } else {
                    asignacionesCache[grupoId].nombre = grupoNombre;
                }

                // Predios seleccionados
                const prediosSeleccionados = getSelectedText($predios);

                prediosSeleccionados.forEach(p => {
                    if (!asignacionesCache[grupoId].predios[p.id]) {
                        asignacionesCache[grupoId].predios[p.id] = { id: p.id, nombre: p.text, zonas: {} };
                    } else {
                        asignacionesCache[grupoId].predios[p.id].nombre = p.text;
                    }
                });

                // Zonas seleccionadas (opcionales)
                const zonasIds = $zonas.val() || [];
                if (zonasIds.length > 0) {
                    // Construir mapa zona -> predio_id desde data attribute si existe
                    const zonaOptions = $zonas.find('option:selected').toArray();
                    const zonasPorPredio = {}; // predioId -> [{id,nombre}]
                    const zonasSinPredio = [];

                    zonaOptions.forEach(opt => {
                        const zonaId = String(opt.value);
                        const zonaNombre = (opt.text || '').trim();
                        const predioIdZona = opt.dataset && opt.dataset.predioId ? String(opt.dataset.predioId) : null;

                        if (predioIdZona) {
                            if (!zonasPorPredio[predioIdZona]) zonasPorPredio[predioIdZona] = [];
                            zonasPorPredio[predioIdZona].push({ id: zonaId, nombre: zonaNombre });
                        } else {
                            zonasSinPredio.push({ id: zonaId, nombre: zonaNombre });
                        }
                    });

                    // Si hay data-predio-id, asigna por predio; si no, solo permite asignar si hay 1 predio
                    const prediosSeleccionadosIds = predioIds.map(String);

                    // Asignación por predio (cuando viene predioId en dataset)
                    Object.keys(zonasPorPredio).forEach(predioId => {
                        if (!asignacionesCache[grupoId].predios[predioId]) {
                            // Si la zona trae predioId pero el predio no está seleccionado, lo ignoramos
                            return;
                        }
                        zonasPorPredio[predioId].forEach(z => {
                            asignacionesCache[grupoId].predios[predioId].zonas[z.id] = { id: z.id, nombre: z.nombre };
                        });
                    });

                    // Si no existe dataset predioId, solo asociar si hay 1 predio
                    if (zonasSinPredio.length > 0) {
                        if (prediosSeleccionadosIds.length === 1) {
                            const onlyPredioId = prediosSeleccionadosIds[0];
                            zonasSinPredio.forEach(z => {
                                asignacionesCache[grupoId].predios[onlyPredioId].zonas[z.id] = { id: z.id, nombre: z.nombre };
                            });
                        } else {
                            Swal.fire({
                                icon: 'info',
                                title: 'Zonas sin predio',
                                text: 'Para asignar zonas con múltiples predios, carga las zonas con su predio (data-predio-id).'
                            });
                        }
                    }
                }

                renderAsignacionesTree();

                //limpiar selects
                $predios.val(null).trigger('change');
                $zonas.val(null).trigger('change');
            }

            function renderAsignacionesTree() {
                const $card = $('#resumen_asignaciones_card');
                const $tree = $('#asignaciones_tree');

                const groupIds = Object.keys(asignacionesCache);

                if (groupIds.length === 0) {
                    $card.hide();
                    $tree.html('');
                    $('#asignaciones_cache').val('');
                    return;
                }

                let html = '<div class="tree"><ul>';

                groupIds.forEach(gid => {
                    const g = asignacionesCache[gid];
                    const predios = g.predios || {};
                    const predioIds = Object.keys(predios);

                    html += `
                        <li>
                            <span class="node">
                                <i class="icon-collaboration"></i>
                                <strong>${escapeHtml(g.nombre)}</strong>
                                <span class="badge badge-primary ml-2">${predioIds.length} predio(s)</span>
                                <span class="actions">
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-remove-group="${gid}">
                                        <i class="icon-trash"></i>
                                    </button>
                                </span>
                            </span>
                            <ul>
                    `;

                    predioIds.forEach(pid => {
                        const p = predios[pid];
                        const zonas = p.zonas || {};
                        const zonaIds = Object.keys(zonas);

                        html += `
                            <li>
                                <span class="node">
                                    <i class="icon-map5"></i>
                                    ${escapeHtml(p.nombre)}
                                    <span class="badge badge-info ml-2">${zonaIds.length} zona(s)</span>
                                    <span class="actions">
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            data-remove-predio="${gid}|${pid}">
                                            <i class="icon-trash"></i>
                                        </button>
                                    </span>
                                </span>
                        `;

                        if (zonaIds.length > 0) {
                            html += '<ul>';
                            zonaIds.forEach(zid => {
                                const z = zonas[zid];
                                html += `
                                    <li>
                                        <span class="node">
                                            <i class="icon-grid6"></i>
                                            ${escapeHtml(z.nombre)}
                                            <span class="actions">
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-remove-zona="${gid}|${pid}|${zid}">
                                                    <i class="icon-trash"></i>
                                                </button>
                                            </span>
                                        </span>
                                    </li>
                                `;
                            });
                            html += '</ul>';
                        }

                        html += '</li>';
                    });

                    html += '</ul></li>';
                });

                html += '</ul></div>';

                $tree.html(html);
                $card.show();

                // Guarda JSON (por si luego lo mandas al backend)
                $('#asignaciones_cache').val(JSON.stringify(asignacionesCache));
            }

            // Remover (delegación)
            $(document).on('click', '[data-remove-group]', function () {
                const gid = String($(this).data('remove-group'));
                delete asignacionesCache[gid];
                renderAsignacionesTree();
            });

            $(document).on('click', '[data-remove-predio]', function () {
                const parts = String($(this).data('remove-predio')).split('|');
                const gid = parts[0], pid = parts[1];
                if (asignacionesCache[gid] && asignacionesCache[gid].predios) {
                    delete asignacionesCache[gid].predios[pid];
                    if (Object.keys(asignacionesCache[gid].predios).length === 0) {
                        delete asignacionesCache[gid];
                    }
                }
                renderAsignacionesTree();
            });

            $(document).on('click', '[data-remove-zona]', function () {
                const parts = String($(this).data('remove-zona')).split('|');
                const gid = parts[0], pid = parts[1], zid = parts[2];
                if (asignacionesCache[gid] && asignacionesCache[gid].predios && asignacionesCache[gid].predios[pid]) {
                    delete asignacionesCache[gid].predios[pid].zonas[zid];
                }
                renderAsignacionesTree();
            });

            function escapeHtml(str) {
    return String(str ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}
    </script>
    <!-- /theme JS files -->
@endsection

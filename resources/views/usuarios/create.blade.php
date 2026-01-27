@extends('layouts.web')
@section('title', $section_name)
@section('modelo', 'Agregar')
@section('content')
    <!-- Form inputs -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

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
                                    <label class="col-form-label col-lg-12">Productor <span class="text-danger">*</span></label>
                                    <select name="cliente_id" class="form-control" required>
                                        <option value="">Seleccione un productor</option>
                                        @foreach ($clientes as $cliente)
                                            <option value="{{ $cliente->id }}">{{ $cliente->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('cliente_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div class="col-3">
                                    <label class="col-form-label col-lg-12">Nombre <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="nombre" class="form-control" placeholder="Nombre">
                                </div>
                                <div class="col-3">
                                    <label class="col-form-label col-lg-12">Correo <span
                                            class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" placeholder="Correo">
                                </div>
                               <div class="col-3">
                                <label class="col-form-label col-lg-12">
                                    Contraseña <span class="text-danger">*</span>
                                </label>

                                <div class="input-group">
                                    <input id="password" type="password" name="password" class="form-control" placeholder="Contraseña">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Mostrar contraseña">
                                    <i class="bi bi-eye" id="togglePasswordIcon"></i>
                                    </button>
                                </div>
                                </div>
                                {{--  <div class="col-3">
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
                                </div>  --}}
                            </div>

                           
                            <div class="row mt-3">
                                <div class="col-6">
                                    <label class="col-form-label col-lg-12">Grupo <span class="text-danger">*</span></label>
                                    <select name="grupo_id[]" multiple id="grupo_id" class="form-control select2">
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
                    @include('usuarios._asignacion_manual', [
                        'gruposDisponibles' => $gruposDisponibles,
                        // opcional para edit: JSON precargado del controller
                        'asignaciones_cache' => $asignaciones_cache ?? null,
                    ])

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

            (function () {
                const input = document.getElementById('password');
                const btn = document.getElementById('togglePassword');
                const icon = document.getElementById('togglePasswordIcon');

                btn.addEventListener('click', () => {
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                icon.classList.toggle('bi-eye', !isPassword);
                icon.classList.toggle('bi-eye-slash', isPassword);
                btn.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
                });
            })();
        });

           // ========= AJAX: predios por grupo =========
    function cargarPredios() {
        const grupoId = $('#grupo_manual_id').val();

        $.ajax({
            url: `/grupos/${grupoId}/predios`,
            method: 'GET',
            success: function (data) {
                const $predios = $('#predio_ids');
                $predios.empty();

                data.forEach(predio => {
                    $predios.append(new Option(predio.nombre, predio.id, false, false));
                });

                $predios.trigger('change');
                $('#zona_ids').empty().trigger('change');
            },
            error: function (xhr) {
                console.error('Error al cargar los predios:', xhr.responseText || xhr);
            }
        });
    }

    // ========= AJAX: zonas por predios =========
    function cargarZonas() {
        const predioIds = $('#predio_ids').val() || [];

        $.ajax({
            url: '/predios/zonas', // recomendado: endpoint que acepte predio_ids[]
            method: 'GET',
            data: { predio_ids: predioIds },
            success: function(data) {
                const $zonas = $('#zona_ids');
                $zonas.empty();

                data.forEach(zona => {
                    const label = (zona.predio?.nombre ? zona.predio.nombre + ' — ' : '') + zona.nombre;
                    const opt = new Option(label, zona.id, false, false);
                    opt.dataset.predioId = zona.predio_id;
                    $zonas.append(opt);
                });

                $zonas.trigger('change');
            },
            error: function(xhr) {
                console.error('Error al cargar las zonas:', xhr);
            }
        });
    }



    // ========= Cache + Árbol =========
    const asignacionesCache = {};

    function getSelectedText($select) {
        const data = $select.select2('data') || [];
        return data.map(x => ({ id: String(x.id), text: x.text }));
    }

    function agregarZonaPredio() {
        const $grupo = $('#grupo_manual_id');
        const $predios = $('#predio_ids');
        const $zonas = $('#zona_ids');

        const grupoId = $grupo.val();
        const predioIds = $predios.val() || [];

        if (!grupoId) {
            Swal.fire({ icon: 'warning', title: 'Falta Grupo', text: 'Seleccione un grupo.' });
            return;
        }
        if (predioIds.length === 0) {
            Swal.fire({ icon: 'warning', title: 'Faltan Predios', text: 'Seleccione al menos un predio.' });
            return;
        }

        const grupoNombre = $grupo.find('option:selected').text().trim();

        if (!asignacionesCache[grupoId]) {
            asignacionesCache[grupoId] = { id: String(grupoId), nombre: grupoNombre, predios: {} };
        } else {
            asignacionesCache[grupoId].nombre = grupoNombre;
        }

        const prediosSeleccionados = getSelectedText($predios);
        prediosSeleccionados.forEach(p => {
            if (!asignacionesCache[grupoId].predios[p.id]) {
                asignacionesCache[grupoId].predios[p.id] = { id: p.id, nombre: p.text, zonas: {} };
            } else {
                asignacionesCache[grupoId].predios[p.id].nombre = p.text;
            }
        });

        const zonasIds = $zonas.val() || [];
        if (zonasIds.length > 0) {
            const zonaOptions = $zonas.find('option:selected').toArray();
            const zonasPorPredio = {};

            zonaOptions.forEach(opt => {
                const zonaId = String(opt.value);
                const zonaNombre = (opt.text || '').trim();
                const predioIdZona = opt.dataset && opt.dataset.predioId ? String(opt.dataset.predioId) : null;

                if (!predioIdZona) return;
                if (!zonasPorPredio[predioIdZona]) zonasPorPredio[predioIdZona] = [];
                zonasPorPredio[predioIdZona].push({ id: zonaId, nombre: zonaNombre });
            });

            Object.keys(zonasPorPredio).forEach(predioId => {
                if (!asignacionesCache[grupoId].predios[predioId]) return;
                zonasPorPredio[predioId].forEach(z => {
                    asignacionesCache[grupoId].predios[predioId].zonas[z.id] = { id: z.id, nombre: z.nombre };
                });
            });
        }

        renderAsignacionesTree();

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

        $('#asignaciones_cache').val(JSON.stringify(asignacionesCache));
    }

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

    // Inicialización común (create/edit)
    document.addEventListener('DOMContentLoaded', function () {
        $('.select2').select2({ allowClear: true });

        // Si en edit quieres precargar el árbol, pasa $asignaciones_cache desde el controller
        const initial = $('#asignaciones_cache').val();
        if (initial) {
            try {
                const parsed = JSON.parse(initial);
                Object.keys(parsed).forEach(gid => asignacionesCache[gid] = parsed[gid]);
                renderAsignacionesTree();
            } catch (e) {
                console.warn('asignaciones_cache inválido:', e);
            }
        }
    });

    </script>
    <!-- /theme JS files -->
@endsection

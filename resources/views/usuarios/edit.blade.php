@extends('layouts.web')
@section('title', $section_name)
@section('modelo', 'Actualizar')
@section('ruta_home', route('usuarios.index', ['id' => $cliente_id]))

@section('content')
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

            <form action="{{ route('usuarios.update', $usuario->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <fieldset class="mb-3">

                    <div class="form-group row">
                        <div class="col-lg-12">
                            <div class="row">

                                {{-- Productor / Cliente --}}
                                {{-- <div class="col-3">
                                    <label class="col-form-label col-lg-12">
                                        Productor <span class="text-danger">*</span>
                                    </label>
                                    <select name="cliente_id" class="form-control" required>
                                        @foreach ($clientes as $productor)
                                            <option value="{{ $productor->id }}"
                                                {{ (int)$usuario->cliente_id === (int)$productor->id ? 'selected' : '' }}>
                                                {{ $productor->nombre }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('cliente_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div> --}}

                                <div class="col-3">
                                    <label class="col-form-label col-lg-12">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" name="nombre" value="{{ old('nombre', $usuario->nombre) }}"
                                           class="form-control" placeholder="Nombre">
                                </div>

                                <div class="col-3">
                                    <label class="col-form-label col-lg-12">Correo <span class="text-danger">*</span></label>
                                    <input type="email" name="email" value="{{ old('email', $usuario->email) }}"
                                           class="form-control" placeholder="Correo">
                                </div>

                                <div class="col-3">
                                    <label class="col-form-label col-lg-12">Contraseña</label>
                                    <input type="password" name="password" class="form-control"
                                           placeholder="Dejar vacío para mantener la actual">
                                    <small class="form-text text-muted">
                                        Dejar vacío si no desea cambiar la contraseña
                                    </small>
                                </div>
                            </div>

                            {{-- Grupos del usuario --}}
                            <div class="row mt-3">
                                <div class="col-6">
                                    <label class="col-form-label col-lg-12">Grupo</label>

                                    @php
                                        $selectedGrupos = collect(old('grupo_id', $gruposAsignadosIds ?? []))
                                            ->map(fn($v) => (string)$v)
                                            ->toArray();
                                    @endphp

                                    <select name="grupo_id[]" id="grupo_id" class="form-control select2" multiple>
                                        @foreach ($gruposDisponibles as $grupo)
                                            <option value="{{ $grupo['id'] }}"
                                                {{ in_array((string)$grupo['id'], $selectedGrupos, true) ? 'selected' : '' }}>
                                                {{ $grupo['nombre'] }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <small class="form-text text-muted">
                                        Asignar uno o varios grupos permite acceso jerárquico a todas las zonas del grupo y sus descendientes.
                                    </small>

                                    @error('grupo_id')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- Partial reusable (manual + resumen + hidden asignaciones_cache precargado) --}}
                    @include('usuarios._asignacion_manual', [
                        'gruposDisponibles' => $gruposDisponibles,
                        'asignaciones_cache' => old('asignaciones_cache', $asignaciones_cache ?? ''),
                    ])

                </fieldset>

                <div class="text-right">
                    <button type="submit" class="btn btn-primary">
                        Actualizar <i class="icon-paperplane ml-2"></i>
                    </button>
                </div>

            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ url('global_assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/styling/switchery.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/selects/select2.min.js') }}"></script>
    <script src="{{ url('assets/js/app.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/form_inputs.js') }}"></script>

    <script>
        // ========= Estado global =========
        let asignacionesCache = {};

        // ========= init =========
        $(document).ready(function() {

            // Select2
            $('.select2').select2({ allowClear: true, width: '100%' });

            // Hydrate cache desde backend (edit)
            hydrateAsignacionesCacheFromHidden();

            // Render inicial
            renderAsignacionesTree();
        });

        function hydrateAsignacionesCacheFromHidden() {
            const raw = ($('#asignaciones_cache').val() || '').trim();
            if (!raw) {
                asignacionesCache = {};
                return;
            }
            try {
                asignacionesCache = JSON.parse(raw);
            } catch (e) {
                console.error('asignaciones_cache inválido:', e, raw);
                asignacionesCache = {};
            }
        }

        // ========= AJAX: predios por grupo =========
        function cargarPredios() {
            const grupoId = $('#grupo_manual_id').val();
            if (!grupoId) return;

            $.ajax({
                url: '/grupos/' + grupoId + '/predios',
                method: 'GET',
                success: function(data) {
                    const $predios = $('#predio_ids');
                    $predios.empty();

                    data.forEach(predio => {
                        $predios.append(new Option(predio.nombre, predio.id, false, false));
                    });

                    $predios.trigger('change');
                    $('#zona_ids').empty().trigger('change');
                },
                error: function(xhr) {
                    console.error('Error al cargar los predios:', xhr.responseText || xhr);
                }
            });
        }

        // ========= AJAX: zonas por predios =========
        function cargarZonas() {
            const predioIds = $('#predio_ids').val() || [];
            if (predioIds.length === 0) {
                $('#zona_ids').empty().trigger('change');
                return;
            }

            $.ajax({
                url: '/predios/zonas',
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
                    console.error('Error al cargar las zonas:', xhr.responseText || xhr);
                }
            });
        }

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

            const zonaOptions = $zonas.find('option:selected').toArray();
            if (zonaOptions.length > 0) {
                zonaOptions.forEach(opt => {
                    const zonaId = String(opt.value);
                    const zonaNombre = (opt.text || '').trim();
                    const predioIdZona = opt.dataset?.predioId ? String(opt.dataset.predioId) : null;

                    if (!predioIdZona || !asignacionesCache[grupoId].predios[predioIdZona]) return;

                    asignacionesCache[grupoId].predios[predioIdZona].zonas[zonaId] = {
                        id: zonaId,
                        nombre: zonaNombre
                    };
                });
            }

            renderAsignacionesTree();

            $predios.val(null).trigger('change');
            $zonas.val(null).trigger('change');
        }

        function renderAsignacionesTree() {
            const $card = $('#resumen_asignaciones_card');
            const $tree = $('#asignaciones_tree');

            const groupIds = Object.keys(asignacionesCache || {});
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
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-remove-predio="${gid}|${pid}">
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
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-remove-zona="${gid}|${pid}|${zid}">
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

            // Persistir en hidden para submit
            $('#asignaciones_cache').val(JSON.stringify(asignacionesCache));
        }

        // Remover handlers
        $(document).on('click', '[data-remove-group]', function () {
            const gid = String($(this).data('remove-group'));
            delete asignacionesCache[gid];
            renderAsignacionesTree();
        });

        $(document).on('click', '[data-remove-predio]', function () {
            const [gid, pid] = String($(this).data('remove-predio')).split('|');
            if (asignacionesCache[gid]?.predios) {
                delete asignacionesCache[gid].predios[pid];
                if (Object.keys(asignacionesCache[gid].predios).length === 0) {
                    delete asignacionesCache[gid];
                }
            }
            renderAsignacionesTree();
        });

        $(document).on('click', '[data-remove-zona]', function () {
            const [gid, pid, zid] = String($(this).data('remove-zona')).split('|');
            if (asignacionesCache[gid]?.predios?.[pid]?.zonas) {
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
@endsection

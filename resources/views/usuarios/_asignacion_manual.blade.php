{{-- resources/views/usuarios/_asignacion_manual.blade.php --}}
{{-- Reutilizable para create y edit --}}

@push('styles')
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
@endpush

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
                                <option value="{{ $grupo['id'] }}">
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

{{-- =========================
    RESUMEN (árbol)
   ========================= --}}
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

        {{-- Se envía al backend en create y edit --}}
        <input type="hidden" name="asignaciones_cache" id="asignaciones_cache" value="{{ old('asignaciones_cache', $asignaciones_cache ?? '') }}">
    </div>
</div>

@push('scripts')
<script>
 

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
@endpush

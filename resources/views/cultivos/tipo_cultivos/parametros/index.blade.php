@extends('layouts.web')
@section('title', $name_routes)
@section('ruta_home', route('tipo_cultivos.index', $cultivo_id))
@section('styles')
    <!-- Theme JS files -->
    <script src="{{ url('global_assets/js/plugins/tables/datatables/datatables.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/tables/datatables/extensions/responsive.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/selects/select2.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/buttons/spin.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/buttons/ladda.min.js') }}"></script>

    <script src="{{ url('assets/js/app.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/datatables_responsive.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/components_buttons.js') }}"></script>
    <!-- /theme JS files -->
@endsection
@section('content')
    <!-- Botones de acción -->
    <div class="mb-3 text-right">
        <button type="button" class="btn btn-info mr-2" data-toggle="modal" data-target="#copiarParametrosModal">
            <i class="icon-copy3"></i> Copiar parámetros
        </button>
        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#parametroModalAgregar">
            <i class="icon-plus22"></i> Nuevo(s) parámetro(s)
        </button>
    </div>
    <!-- Basic responsive configuration -->
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
            {{ $section_description }}
        </div>

        <table class="table datatable-responsive">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($list as $row)
                    <tr>
                        <td>{{ $row->nombre ?? 'Sin nombre' }}</td>
                        <td class="text-center">
                            <div class="list-icons">
                                <a href="#" class="list-icons-item text-primary-600 edit-parametro-btn"
                                    data-id="{{ $row->id }}">
                                    <i class="icon-pencil7"></i>
                                </a>
                                <form
                                    action="{{ route('parametros.destroy', ['id' => $cultivo_id, 'tipo_cultivo' => $tipo_cultivo_id, 'parametro' => $row->id]) }}"
                                    method="POST" class="d-inline delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn p-0 pl-3 border-0 bg-transparent delete-button"
                                        data-name="{{ $row->nombre }}" title="Eliminar">
                                        <i class="icon-trash text-danger-600"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="text-center">No hay registros disponibles.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <!-- /basic responsive configuration -->
    <!-- Modal para editar parámetros -->
    <div class="modal fade" id="parametroModalEditar" tabindex="-1" role="dialog"
        aria-labelledby="parametroModalEditarLabel" aria-hidden="true">
        <div class="modal-dialog modal-xxl" role="document" style="max-width: 95vw;">
            <form>
                <input type="hidden" name="tipo_cultivo_id" value="{{ $tipo_cultivo_id }}">
                <input type="hidden" name="etapa_fenologica_id" id="etapa_fenologica_id_editar">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="parametroModalEditarLabel">Editar parámetros</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered" id="parametros-table-editar">
                            <thead>
                                <tr>
                                    <th>Elemento/índice</th>
                                    <th>Min</th>
                                    <th>Óptimo bajo</th>
                                    <th>Óptimo max</th>
                                    <th>max</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal para agregar parámetros -->
    <div class="modal fade" id="parametroModalAgregar" tabindex="-1" role="dialog"
        aria-labelledby="parametroModalAgregarLabel" aria-hidden="true">
        <div class="modal-dialog modal-xxl" role="document" style="max-width: 95vw;">
            <form>
                <input type="hidden" name="tipo_cultivo_id" value="{{ $tipo_cultivo_id }}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="parametroModalAgregarLabel">Agregar etapa fenológica y nuevos
                            parámetros
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered" id="parametros-table-agregar">
                            <thead>
                                <tr>
                                    <th colspan="6">
                                        <label for="etapa_fenologica_id">Etapa fenológica</label>
                                        <select name="etapa_fenologica_id" class="form-control">
                                            @foreach ($etapas_fenologicas as $etapa)
                                                <option value="{{ $etapa->id }}">{{ $etapa->nombre }}</option>
                                            @endforeach
                                        </select>
                                    </th>
                                </tr>
                                <tr>
                                    <th>Elemento/índice</th>
                                    <th>Min</th>
                                    <th>Óptimo bajo</th>
                                    <th>Óptimo max</th>
                                    <th>max</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><input type="text" name="variable[]" class="form-control" required></td>
                                    <td><input type="text" name="min[]" class="form-control"></td>
                                    <td><input type="text" name="optimo_min[]" class="form-control"></td>
                                    <td><input type="text" name="optimo_max[]" class="form-control"></td>
                                    <td><input type="text" name="max[]" class="form-control"></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-danger btn-sm remove-row-agregar"
                                            title="Eliminar fila">
                                            <i class="icon-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-success btn-sm" id="add-row-agregar">
                            <i class="icon-plus22"></i> Agregar fila
                        </button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para copiar parámetros -->
    <div class="modal fade" id="copiarParametrosModal" tabindex="-1" role="dialog"
        aria-labelledby="copiarParametrosModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="copiarParametrosModalLabel">Copiar parámetros entre etapas fenológicas
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label for="etapa_origen">Etapa de origen:</label>
                            <select id="etapa_origen" class="form-control">
                                <option value="">Selecciona una etapa...</option>
                                @foreach ($list as $etapa)
                                    <option value="{{ $etapa->id }}">{{ $etapa->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="etapa_destino">Etapa de destino:</label>
                            <select id="etapa_destino" class="form-control">
                                <option value="">Selecciona una etapa...</option>
                                @foreach ($list as $etapa)
                                    <option value="{{ $etapa->id }}">{{ $etapa->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div id="parametros_preview" style="display: none;">
                            <h6>Parámetros a copiar:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Variable</th>
                                            <th>Min</th>
                                            <th>Óptimo bajo</th>
                                            <th>Óptimo max</th>
                                            <th>Max</th>
                                        </tr>
                                    </thead>
                                    <tbody id="parametros_preview_body"></tbody>
                                </table>
                            </div>
                        </div>
                        <div id="cargando_parametros" style="display: none;">
                            <div class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Cargando...</span>
                                </div>
                                <p class="mt-2 mb-0">Cargando parámetros de la etapa seleccionada...</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnCopiarParametros" disabled>
                        <i class="icon-copy3"></i> Copiar parámetros
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $(document).on('click', '.delete-button', function(e) {
            e.preventDefault();
            const form = $(this).closest('form');
            const name = $(this).data('name') ?? '¿Estás seguro?';

            Swal.fire({
                title: '¿Estás seguro?',
                text: `Vas a eliminar: ${name}`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
        // --- MODAL AGREGAR ---
        $(document).ready(function() {
            // Al abrir el modal de agregar, poner las 5 variables por defecto
            $('#parametroModalAgregar').on('show.bs.modal', function() {
                var nomenclatura = ['pH', 'C.E. Ds/m', 'N ppm', 'P ppm', 'K ppm', 'Temperatura °C',
                    'Humedad Relativa %', 'CO2 ppm', 'Radiación Solar kJ/m2',
                    'Temperatura del Suelo °C', 'Humedad del Suelo %', 'Velocidad del Viento m/s',
                    'Presión Atmosférica hPa'
                ];
                var variables = ['ph', 'conductividad_electrica', 'nit', 'phos', 'pot', 'temperatura',
                    'humedad_relativa', 'co2', 'radiacion_solar', 'temperatura_suelo', 'humedad_15',
                    'velocidad_viento', 'presion_atmosferica'
                ];
                var tbody = $('#parametros-table-agregar tbody');
                tbody.html('');
                variables.forEach(function(variable, idx) {
                    let placeholder = nomenclatura[idx] || variable;
                    let row = `<tr>
                        <td>
                            <input type="hidden" name="variable[]" value="${variable}">
                            <input type="text" class="form-control" placeholder="${placeholder}" readonly>
                        </td>
                        <td><input type="text" name="min[]" class="form-control"></td>
                        <td><input type="text" name="optimo_min[]" class="form-control"></td>
                        <td><input type="text" name="optimo_max[]" class="form-control"></td>
                        <td><input type="text" name="max[]" class="form-control"></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm remove-row-agregar" title="Eliminar fila">
                                <i class="icon-trash"></i>
                            </button>
                        </td>
                    </tr>`;
                    tbody.append(row);
                });
            });
            // Agregar nueva fila en modal agregar
            $('#add-row-agregar').click(function() {
                let row = `<tr>
                    <td>
                        <input type="hidden" name="variable[]">
                        <input type="text" class="form-control" placeholder="Variable" readonly>
                    </td>
                    <td><input type="text" name="min[]" class="form-control"></td>
                    <td><input type="text" name="optimo_min[]" class="form-control"></td>
                    <td><input type="text" name="optimo_max[]" class="form-control"></td>
                    <td><input type="text" name="max[]" class="form-control"></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-danger btn-sm remove-row-agregar" title="Eliminar fila">
                            <i class="icon-trash"></i>
                        </button>
                    </td>
                </tr>`;
                $('#parametros-table-agregar tbody').append(row);
            });
            // Eliminar fila en modal agregar
            $(document).on('click', '.remove-row-agregar', function() {
                if ($('#parametros-table-agregar tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                }
            });
            // Submit modal agregar
            $('#parametroModalAgregar form').on('submit', function(e) {
                e.preventDefault();
                var tipo_cultivo_id = $('input[name="tipo_cultivo_id"]').val();
                var requests = [];
                var variablesData = [];

                // Recolectar datos y validar
                $('#parametroModalAgregar tbody tr').each(function() {
                    var row = $(this);
                    var variable = row.find('input[name="variable[]"]').val();
                    var data = {
                        etapa_fenologica_id: $('select[name="etapa_fenologica_id"]').val(),
                        tipo_cultivo_id: tipo_cultivo_id,
                        variable: variable,
                        min: row.find('input[name="min[]"]').val(),
                        optimo_min: row.find('input[name="optimo_min[]"]').val(),
                        optimo_max: row.find('input[name="optimo_max[]"]').val(),
                        max: row.find('input[name="max[]"]').val(),
                    };
                    variablesData.push({
                        variable: variable,
                        data: data
                    });
                    requests.push(axios.post('/api/nutricion-etapa-fenologica-tipo-cultivo', data));
                });

                // Mostrar indicador de progreso con animación
                var progressModal = Swal.fire({
                    title: 'Guardando parámetros...',
                    html: '<div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div></div>',
                    allowOutsideClick: false,
                    didOpen: () => {
                        // Animar la barra de progreso
                        var progressBar = document.querySelector('.progress-bar');
                        var width = 0;
                        var interval = setInterval(function() {
                            if (width >= 90) {
                                clearInterval(interval);
                            } else {
                                width += Math.random() * 15;
                                progressBar.style.width = width + '%';
                            }
                        }, 200);
                    }
                });

                Promise.allSettled(requests)
                    .then(function(results) {
                        var successCount = 0;
                        var errors = [];

                        results.forEach(function(result, index) {
                            if (result.status === 'fulfilled') {
                                successCount++;
                            } else {
                                var variableName = variablesData[index].variable;
                                var errorMsg = result.reason?.response?.data?.message ||
                                    'Error desconocido';
                                errors.push(`${variableName}: ${errorMsg}`);
                            }
                        });

                        progressModal.close();

                        if (errors.length === 0) {
                            // Todos exitosos
                            Swal.fire('¡Guardado!',
                                `Se guardaron ${successCount} parámetros correctamente.`, 'success');
                            $('#parametroModalAgregar').modal('hide');
                            location.reload();
                        } else if (successCount > 0) {
                            // Parcialmente exitoso
                            Swal.fire({
                                title: 'Guardado parcial',
                                html: `
                                    <p>Se guardaron ${successCount} de ${results.length} parámetros.</p>
                                    <details>
                                        <summary>Errores encontrados:</summary>
                                        <ul class="text-left">
                                            ${errors.map(error => `<li>${error}</li>`).join('')}
                                        </ul>
                                    </details>
                                `,
                                icon: 'warning',
                                confirmButtonText: 'Entendido'
                            });
                        } else {
                            // Todos fallaron
                            Swal.fire({
                                title: 'Error al guardar',
                                html: `
                                    <p>No se pudo guardar ningún parámetro.</p>
                                    <details>
                                        <summary>Errores encontrados:</summary>
                                        <ul class="text-left">
                                            ${errors.map(error => `<li>${error}</li>`).join('')}
                                        </ul>
                                    </details>
                                `,
                                icon: 'error',
                                confirmButtonText: 'Entendido'
                            });
                        }
                    })
                    .catch(function(error) {
                        progressModal.close();
                        Swal.fire('Error', 'Error inesperado al procesar la solicitud', 'error');
                    });
            });
        });

        // --- MODAL EDITAR ---
        $(document).on('click', '.edit-parametro-btn', function(e) {
            e.preventDefault();
            var etapaFenologicaId = $(this).data('id');
            var tipo_cultivo_id = $('input[name="tipo_cultivo_id"]').val();
            var variables = ['ph', 'conductividad_electrica', 'nit', 'phos', 'pot', 'temperatura',
                'humedad_relativa', 'co2', 'radiacion_solar', 'temperatura_suelo', 'humedad_15',
                'velocidad_viento', 'presion_atmosferica'
            ];
            $('#etapa_fenologica_id_editar').val(etapaFenologicaId);
            $('#parametros-table-editar tbody').html('');
            let requests = variables.map(variable =>
                axios.get('/api/nutricion-etapa-fenologica-tipo-cultivo/buscar', {
                    params: {
                        etapa_fenologica_id: etapaFenologicaId,
                        tipo_cultivo_id: tipo_cultivo_id,
                        variable: variable
                    }
                }).then(function(response) {
                    if (!response.data.variable) response.data.variable = variable;
                    return response.data;
                }).catch(function() {
                    return {
                        variable: variable,
                        min: '',
                        optimo_min: '',
                        optimo_max: '',
                        max: '',
                        id: null,
                        etapa_fenologica_tipo_cultivo_id: null
                    };
                })
            );
            Promise.all(requests).then(function(results) {
                renderizarTablaEditar(results);
            });
        });

        function renderizarTablaEditar(results) {
            $('#parametros-table-editar tbody').html('');
            results.forEach(function(data, idx) {
                var nomenclatura = ['pH', 'C.E. Ds/m', 'N ppm', 'P ppm', 'K ppm',
                    'Temperatura °C', 'Humedad Relativa %', 'CO2 ppm',
                    'Radiación Solar kJ/m2', 'Temperatura del Suelo °C',
                    'Humedad del Suelo %', 'Velocidad del Viento m/s', 'Presión Atmosférica hPa'
                ];
                let placeholder = nomenclatura[idx] || data.variable;
                let row = `<tr${data.id ? ' data-id="' + data.id + '"' : ''} data-etapa-fenologica-tipo-cultivo-id="${data.etapa_fenologica_tipo_cultivo_id ?? ''}">
                        <td>
                            <input type="hidden" name="variable[]" value="${data.variable}">
                            <input type="text" class="form-control" placeholder="${placeholder}" value="${placeholder}" readonly>
                        </td>
                        <td><input type="text" name="min[]" class="form-control" value="${data.min ?? ''}"></td>
                        <td><input type="text" name="optimo_min[]" class="form-control" value="${data.optimo_min ?? ''}"></td>
                        <td><input type="text" name="optimo_max[]" class="form-control" value="${data.optimo_max ?? ''}"></td>
                        <td><input type="text" name="max[]" class="form-control" value="${data.max ?? ''}"></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-danger btn-sm remove-row-editar" title="Eliminar fila">
                                <i class="icon-trash"></i>
                            </button>
                        </td>
                    </tr>`;
                $('#parametros-table-editar tbody').append(row);
            });
            $('#parametroModalEditar').data('edit-id', $('#etapa_fenologica_id_editar').val());
            $('#parametroModalEditar').modal('show');
        }

        // Eliminar fila en modal editar (opcional, puedes deshabilitar si no quieres permitirlo)
        $(document).on('click', '.remove-row-editar', function() {
            if ($('#parametros-table-editar tbody tr').length > 1) {
                $(this).closest('tr').remove();
            }
        });
        // Submit modal editar
        $('#parametroModalEditar form').on('submit', function(e) {
            e.preventDefault();
            var requests = [];
            var variablesData = [];

            // Recolectar datos
            $('#parametros-table-editar tbody tr').each(function(index) {
                var row = $(this);
                var id = row.data('id');
                var variable = row.find('input[name="variable[]"]').val();
                var etapa_fenologica_tipo_cultivo_id = row.data('etapa-fenologica-tipo-cultivo-id');
                var data = {
                    etapa_fenologica_tipo_cultivo_id: etapa_fenologica_tipo_cultivo_id,
                    variable: variable,
                    min: row.find('input[name="min[]"]').val(),
                    optimo_min: row.find('input[name="optimo_min[]"]').val(),
                    optimo_max: row.find('input[name="optimo_max[]"]').val(),
                    max: row.find('input[name="max[]"]').val(),
                };

                variablesData.push({
                    variable: variable,
                    id: id,
                    isUpdate: !!id
                });

                if (id) {
                    requests.push(axios.put('/api/nutricion-etapa-fenologica-tipo-cultivo/' + id, data));
                } else {
                    requests.push(axios.post('/api/nutricion-etapa-fenologica-tipo-cultivo', data));
                }
            });

            // Mostrar indicador de progreso con animación
            var progressModal = Swal.fire({
                title: 'Actualizando parámetros...',
                html: '<div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div></div>',
                allowOutsideClick: false,
                didOpen: () => {
                    // Animar la barra de progreso
                    var progressBar = document.querySelector('.progress-bar');
                    var width = 0;
                    var interval = setInterval(function() {
                        if (width >= 90) {
                            clearInterval(interval);
                        } else {
                            width += Math.random() * 15;
                            progressBar.style.width = width + '%';
                        }
                    }, 200);
                }
            });

            Promise.allSettled(requests)
                .then(function(results) {
                    var successCount = 0;
                    var errors = [];

                    results.forEach(function(result, index) {
                        if (result.status === 'fulfilled') {
                            successCount++;
                        } else {
                            var variableName = variablesData[index].variable;
                            var action = variablesData[index].isUpdate ? 'actualizar' : 'crear';
                            var errorMsg = result.reason?.response?.data?.message ||
                                'Error desconocido';
                            errors.push(`${variableName} (${action}): ${errorMsg}`);
                        }
                    });

                    progressModal.close();

                    if (errors.length === 0) {
                        // Todos exitosos
                        Swal.fire('¡Actualizado!',
                            `Se ${variablesData.some(v => v.isUpdate) ? 'actualizaron' : 'crearon'} ${successCount} parámetros correctamente.`,
                            'success');
                        $('#parametroModalEditar').modal('hide');
                        $('#parametroModalEditar').removeData('edit-id');
                        location.reload();
                    } else if (successCount > 0) {
                        // Parcialmente exitoso
                        Swal.fire({
                            title: 'Actualización parcial',
                            html: `
                                <p>Se procesaron ${successCount} de ${results.length} parámetros.</p>
                                <details>
                                    <summary>Errores encontrados:</summary>
                                    <ul class="text-left">
                                        ${errors.map(error => `<li>${error}</li>`).join('')}
                                    </ul>
                                </details>
                            `,
                            icon: 'warning',
                            confirmButtonText: 'Entendido'
                        });
                    } else {
                        // Todos fallaron
                        Swal.fire({
                            title: 'Error al actualizar',
                            html: `
                                <p>No se pudo procesar ningún parámetro.</p>
                                <details>
                                    <summary>Errores encontrados:</summary>
                                    <ul class="text-left">
                                        ${errors.map(error => `<li>${error}</li>`).join('')}
                                    </ul>
                                </details>
                            `,
                            icon: 'error',
                            confirmButtonText: 'Entendido'
                        });
                    }
                })
                .catch(function(error) {
                    progressModal.close();
                    Swal.fire('Error', 'Error inesperado al procesar la solicitud', 'error');
                });
        });
        // Limpieza al cerrar modales
        $('#parametroModalEditar, #parametroModalAgregar').on('hidden.bs.modal', function() {
            $(this).find('tbody').html('');
            $(this).removeData('edit-id');
        });

        // --- FUNCIONALIDAD DE COPIAR PARÁMETROS ---
        var parametrosOrigen = null;

        // Cargar parámetros cuando se selecciona etapa de origen
        $('#etapa_origen').on('change', function() {
            var etapaId = $(this).val();
            var tipo_cultivo_id = $('input[name="tipo_cultivo_id"]').val();

            if (!etapaId) {
                $('#parametros_preview').hide();
                $('#cargando_parametros').hide();
                $('#btnCopiarParametros').prop('disabled', true);
                return;
            }

            // Mostrar cargador
            $('#parametros_preview').hide();
            $('#cargando_parametros').show();
            $('#btnCopiarParametros').prop('disabled', true);

            var variables = ['ph', 'conductividad_electrica', 'nit', 'phos', 'pot', 'temperatura',
                'humedad_relativa', 'co2', 'radiacion_solar', 'temperatura_suelo', 'humedad_15',
                'velocidad_viento', 'presion_atmosferica'
            ];

            // Cargar parámetros de la etapa origen
            let requests = variables.map(variable =>
                axios.get('/api/nutricion-etapa-fenologica-tipo-cultivo/buscar', {
                    params: {
                        etapa_fenologica_id: etapaId,
                        tipo_cultivo_id: tipo_cultivo_id,
                        variable: variable
                    }
                }).then(function(response) {
                    if (!response.data.variable) response.data.variable = variable;
                    return response.data;
                }).catch(function() {
                    return {
                        variable: variable,
                        min: '',
                        optimo_min: '',
                        optimo_max: '',
                        max: '',
                        id: null,
                        etapa_fenologica_tipo_cultivo_id: null
                    };
                })
            );

            Promise.all(requests).then(function(results) {
                parametrosOrigen = results;
                $('#cargando_parametros').hide();
                mostrarPreviewParametros(results);
                validarFormularioCopia();
            }).catch(function(error) {
                $('#cargando_parametros').hide();
                Swal.fire('Error', 'Error al cargar los parámetros de la etapa seleccionada', 'error');
            });
        });

        // Validar formulario de copia
        function validarFormularioCopia() {
            var etapaOrigen = $('#etapa_origen').val();
            var etapaDestino = $('#etapa_destino').val();

            if (etapaOrigen && etapaDestino && etapaOrigen !== etapaDestino) {
                $('#btnCopiarParametros').prop('disabled', false);
            } else {
                $('#btnCopiarParametros').prop('disabled', true);
            }
        }

        // Mostrar preview de parámetros
        function mostrarPreviewParametros(parametros) {
            var nomenclatura = ['pH', 'C.E. Ds/m', 'N ppm', 'P ppm', 'K ppm',
                'Temperatura °C', 'Humedad Relativa %', 'CO2 ppm',
                'Radiación Solar kJ/m2', 'Temperatura del Suelo °C',
                'Humedad del Suelo %', 'Velocidad del Viento m/s', 'Presión Atmosférica hPa'
            ];

            var tbody = $('#parametros_preview_body');
            tbody.html('');

            parametros.forEach(function(param, idx) {
                var nombre = nomenclatura[idx] || param.variable;
                var row = `<tr>
                    <td>${nombre}</td>
                    <td>${param.min || '-'}</td>
                    <td>${param.optimo_min || '-'}</td>
                    <td>${param.optimo_max || '-'}</td>
                    <td>${param.max || '-'}</td>
                </tr>`;
                tbody.append(row);
            });

            $('#parametros_preview').show();
        }

        // Validar cuando cambia etapa destino
        $('#etapa_destino').on('change', validarFormularioCopia);

        // Ejecutar copia de parámetros
        $('#btnCopiarParametros').on('click', function() {
            if (!parametrosOrigen) {
                Swal.fire('Error', 'No hay parámetros para copiar', 'error');
                return;
            }

            var etapaDestino = $('#etapa_destino').val();
            var tipo_cultivo_id = $('input[name="tipo_cultivo_id"]').val();
            var requests = [];
            var variablesData = [];

            // Crear requests para copiar parámetros
            parametrosOrigen.forEach(function(param) {
                if (param.min || param.optimo_min || param.optimo_max || param.max) {
                    var data = {
                        etapa_fenologica_id: etapaDestino,
                        tipo_cultivo_id: tipo_cultivo_id,
                        variable: param.variable,
                        min: param.min,
                        optimo_min: param.optimo_min,
                        optimo_max: param.optimo_max,
                        max: param.max,
                    };
                    variablesData.push({
                        variable: param.variable,
                        data: data
                    });
                    requests.push(axios.post('/api/nutricion-etapa-fenologica-tipo-cultivo', data));
                }
            });

            if (requests.length === 0) {
                Swal.fire('Advertencia', 'No hay parámetros con valores para copiar', 'warning');
                return;
            }

            // Mostrar indicador de progreso con animación
            var progressModal = Swal.fire({
                title: 'Copiando parámetros...',
                html: '<div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div></div>',
                allowOutsideClick: false,
                didOpen: () => {
                    // Animar la barra de progreso
                    var progressBar = document.querySelector('.progress-bar');
                    var width = 0;
                    var interval = setInterval(function() {
                        if (width >= 90) {
                            clearInterval(interval);
                        } else {
                            width += Math.random() * 15;
                            progressBar.style.width = width + '%';
                        }
                    }, 200);
                }
            });

            Promise.allSettled(requests)
                .then(function(results) {
                    var successCount = 0;
                    var errors = [];

                    results.forEach(function(result, index) {
                        if (result.status === 'fulfilled') {
                            successCount++;
                        } else {
                            var variableName = variablesData[index].variable;
                            var errorMsg = result.reason?.response?.data?.message ||
                                'Error desconocido';
                            errors.push(`${variableName}: ${errorMsg}`);
                        }
                    });

                    progressModal.close();

                    if (errors.length === 0) {
                        Swal.fire('¡Copiado exitoso!',
                            `Se copiaron ${successCount} parámetros correctamente.`, 'success');
                        $('#copiarParametrosModal').modal('hide');
                        location.reload();
                    } else if (successCount > 0) {
                        Swal.fire({
                            title: 'Copia parcial',
                            html: `
                                <p>Se copiaron ${successCount} de ${results.length} parámetros.</p>
                                <details>
                                    <summary>Errores encontrados:</summary>
                                    <ul class="text-left">
                                        ${errors.map(error => `<li>${error}</li>`).join('')}
                                    </ul>
                                </details>
                            `,
                            icon: 'warning',
                            confirmButtonText: 'Entendido'
                        });
                    } else {
                        Swal.fire({
                            title: 'Error al copiar',
                            html: `
                                <p>No se pudo copiar ningún parámetro.</p>
                                <details>
                                    <summary>Errores encontrados:</summary>
                                    <ul class="text-left">
                                        ${errors.map(error => `<li>${error}</li>`).join('')}
                                    </ul>
                                </details>
                            `,
                            icon: 'error',
                            confirmButtonText: 'Entendido'
                        });
                    }
                })
                .catch(function(error) {
                    progressModal.close();
                    Swal.fire('Error', 'Error inesperado al procesar la solicitud', 'error');
                });
        });

        // Limpiar modal de copia al cerrar
        $('#copiarParametrosModal').on('hidden.bs.modal', function() {
            $('#etapa_origen').val('');
            $('#etapa_destino').val('');
            $('#parametros_preview').hide();
            $('#cargando_parametros').hide();
            $('#btnCopiarParametros').prop('disabled', true);
            parametrosOrigen = null;
        });
    </script>
@endsection

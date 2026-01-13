@extends('layouts.web')
@section('title', $section_name)
@section('modelo', 'Agregar')
@section('ruta_home', route('clientes.index'))
@section('styles')
    <!-- Theme JS files -->
    <script src="{{ url('global_assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/styling/switchery.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/styling/switch.min.js') }}"></script>

    <script src="{{ url('assets/js/app.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/form_checkboxes_radios.js') }}"></script>
    <!-- /theme JS files -->
@endsection
@section('scripts')
    <script>
        $(document).ready(function() {
            // Función para desactivar permisos CRUD de un menú
            function disableCrudPermissions(menuKey, container) {
                const crudActions = ['create', 'edit', 'delete'];
                crudActions.forEach(function(action) {
                    const crudCheckbox = container.find(`input[name="crud_permissions[${menuKey}][${action}]"]`);
                    if (crudCheckbox.length > 0) {
                        crudCheckbox.prop('checked', false);
                        crudCheckbox.prop('disabled', true);
                        // Asegurar que se envíe valor 0
                        if (crudCheckbox.siblings('input[type="hidden"][name="crud_permissions[' + menuKey + '][' + action + ']"]').length === 0) {
                            crudCheckbox.after('<input type="hidden" name="crud_permissions[' + menuKey + '][' + action + ']" value="0">');
                        }
                    } else {
                        // Si no existe el checkbox, crear hidden input
                        if (container.find('input[type="hidden"][name="crud_permissions[' + menuKey + '][' + action + ']"]').length === 0) {
                            container.append('<input type="hidden" name="crud_permissions[' + menuKey + '][' + action + ']" value="0">');
                        }
                    }
                });
            }

            // Función para habilitar permisos CRUD de un menú
            function enableCrudPermissions(menuKey, container) {
                const crudActions = ['create', 'edit', 'delete'];
                crudActions.forEach(function(action) {
                    const crudCheckbox = container.find(`input[name="crud_permissions[${menuKey}][${action}]"]`);
                    if (crudCheckbox.length > 0) {
                        crudCheckbox.prop('disabled', false);
                        // Remover hidden inputs si existen
                        crudCheckbox.siblings('input[type="hidden"][name="crud_permissions[' + menuKey + '][' + action + ']"]').remove();
                    }
                });
            }

            // Cuando se desmarca un menú principal, deshabilitar todas sus secundarias y CRUD
            $(document).on('change', '.main-menu-checkbox', function() {
                const mainKey = $(this).data('main-key');
                const isChecked = $(this).is(':checked');
                const mainCard = $(this).closest('.card');
                
                // Desactivar/activar CRUD del menú principal
                if (!isChecked) {
                    disableCrudPermissions(mainKey, mainCard);
                } else {
                    enableCrudPermissions(mainKey, mainCard);
                }
                
                // Habilitar/deshabilitar y desmarcar secundarias
                $(`.sub-menu-checkbox[data-main-key="${mainKey}"]`).each(function() {
                    const subMenuKey = $(this).attr('name').match(/\[(.*?)\]/)[1];
                    const subMenuContainer = $(this).closest('.col-md-6');
                    $(this).prop('disabled', !isChecked);
                    if (!isChecked) {
                        $(this).prop('checked', false);
                        // Desactivar CRUD del submenú
                        disableCrudPermissions(subMenuKey, subMenuContainer);
                        // Agregar hidden input para enviar valor 0 cuando está deshabilitado
                        if ($(this).closest('.form-check-label').find('input[type="hidden"][name="menu_permissions[' + subMenuKey + ']"]').length === 0) {
                            $(this).before('<input type="hidden" name="menu_permissions[' + subMenuKey + ']" value="0">');
                        }
                    } else {
                        // Remover hidden input si está habilitado
                        $(this).closest('.form-check-label').find('input[type="hidden"][name="menu_permissions[' + subMenuKey + ']"]').remove();
                    }
                });
                
                // Manejar submenús anidados
                $(`.nested-sub-menu-checkbox[data-main-key="${mainKey}"]`).each(function() {
                    const nestedKey = $(this).attr('name').match(/\[(.*?)\]/)[1];
                    const nestedContainer = $(this).closest('.ml-4');
                    $(this).prop('disabled', !isChecked);
                    if (!isChecked) {
                        $(this).prop('checked', false);
                        // Desactivar CRUD del submenú anidado
                        disableCrudPermissions(nestedKey, nestedContainer);
                        // Agregar hidden input para enviar valor 0 cuando está deshabilitado
                        if ($(this).closest('.form-check-label').find('input[type="hidden"][name="menu_permissions[' + nestedKey + ']"]').length === 0) {
                            $(this).before('<input type="hidden" name="menu_permissions[' + nestedKey + ']" value="0">');
                        }
                    } else {
                        // Remover hidden input si está habilitado
                        $(this).closest('.form-check-label').find('input[type="hidden"][name="menu_permissions[' + nestedKey + ']"]').remove();
                    }
                });
            });
            
            // Cuando se desmarca un submenú, deshabilitar sus submenús anidados y CRUD
            $(document).on('change', '.sub-menu-checkbox', function() {
                const mainKey = $(this).data('main-key');
                const subMenuKey = $(this).attr('name').match(/\[(.*?)\]/)[1];
                const subMenuContainer = $(this).closest('.col-md-6');
                const parentKey = subMenuContainer.find('.nested-sub-menu-checkbox').first().data('parent-key');
                const isChecked = $(this).is(':checked');
                
                // Desactivar/activar CRUD del submenú
                if (!isChecked) {
                    disableCrudPermissions(subMenuKey, subMenuContainer);
                } else {
                    enableCrudPermissions(subMenuKey, subMenuContainer);
                }
                
                if (parentKey) {
                    $(`.nested-sub-menu-checkbox[data-parent-key="${parentKey}"][data-main-key="${mainKey}"]`).each(function() {
                        const nestedKey = $(this).attr('name').match(/\[(.*?)\]/)[1];
                        const nestedContainer = $(this).closest('.ml-4');
                        $(this).prop('disabled', !isChecked);
                        if (!isChecked) {
                            $(this).prop('checked', false);
                            // Desactivar CRUD del submenú anidado
                            disableCrudPermissions(nestedKey, nestedContainer);
                            // Agregar hidden input para enviar valor 0 cuando está deshabilitado
                            if ($(this).closest('.form-check-label').find('input[type="hidden"][name="menu_permissions[' + nestedKey + ']"]').length === 0) {
                                $(this).before('<input type="hidden" name="menu_permissions[' + nestedKey + ']" value="0">');
                            }
                        } else {
                            // Remover hidden input si está habilitado
                            $(this).closest('.form-check-label').find('input[type="hidden"][name="menu_permissions[' + nestedKey + ']"]').remove();
                        }
                    });
                }
            });
        });
    </script>
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

            <form action="{{ route('usuarios.roles_store', ['id' => $cliente_id]) }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="cliente_id" value="{{ $cliente_id }}">
                <input type="hidden" name="user_id" value="{{ $usuario->id }}">

                <!-- Roles -->
                <fieldset class="mb-4">
                    <legend class="text-uppercase font-size-sm font-weight-bold">Roles</legend>
                    <div class="form-group row">
                        <div class="col-lg-12">
                            <div class="form-group mb-3 mb-md-2">
                                <div class="row">
                                    <div class="col-md-6">
                                        @foreach ($roles as $row)
                                            <div class="form-check">
                                                <label class="form-check-label">
                                                    <input type="radio" name="role_id" value="{{ $row->id }}"
                                                        @checked($row->id == $usuario->role_id) class="form-check-input-styled-primary"
                                                        data-fouc>
                                                    {{ $row->nombre }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </fieldset>

                <!-- Permisos de Menú -->
                <fieldset class="mb-4">
                    <legend class="text-uppercase font-size-sm font-weight-bold">Permisos del Sidebar</legend>
                    <p class="text-muted mb-3">Seleccione los menús principales y secundarios que el usuario puede ver en el sidebar.</p>
                    
                    @foreach ($menuStructure as $mainMenu)
                        @php
                            $mainMenuKey = $mainMenu['key'];
                            $mainPermission = $permissions[$mainMenuKey] ?? null;
                            $mainPermitted = $mainPermission ? $mainPermission['permitted'] : true;
                            $mainCanCreate = $mainPermission ? ($mainPermission['can_create'] ?? true) : true;
                            $mainCanEdit = $mainPermission ? ($mainPermission['can_edit'] ?? true) : true;
                            $mainCanDelete = $mainPermission ? ($mainPermission['can_delete'] ?? true) : true;
                        @endphp
                        
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <div class="form-check">
                                    <label class="form-check-label font-weight-semibold">
                                        <input type="checkbox" 
                                               name="menu_permissions[{{ $mainMenuKey }}]" 
                                               value="1"
                                               class="form-check-input-styled-primary main-menu-checkbox"
                                               data-main-key="{{ $mainMenuKey }}"
                                               @checked($mainPermitted)
                                               data-fouc>
                                        <i class="{{ $mainMenu['icon'] }} mr-2"></i>
                                        {{ $mainMenu['name'] }}
                                    </label>
                                </div>
                                @if ($mainPermitted)
                                    <div class="mt-2 ml-4">
                                        <small class="text-muted font-weight-semibold d-block mb-2">Permisos CRUD:</small>
                                        <div class="d-flex flex-column">
                                            <div class="form-check mb-1">
                                                <label class="form-check-label">
                                                    <input type="checkbox" 
                                                           name="crud_permissions[{{ $mainMenuKey }}][create]" 
                                                           value="1"
                                                           class="form-check-input-styled-primary"
                                                           @checked($mainCanCreate)
                                                           data-fouc>
                                                    <span class="ml-2"><i class="icon-plus-circle2 text-success"></i> Crear</span>
                                                </label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <label class="form-check-label">
                                                    <input type="checkbox" 
                                                           name="crud_permissions[{{ $mainMenuKey }}][edit]" 
                                                           value="1"
                                                           class="form-check-input-styled-primary"
                                                           @checked($mainCanEdit)
                                                           data-fouc>
                                                    <span class="ml-2"><i class="icon-pencil7 text-primary"></i> Editar</span>
                                                </label>
                                            </div>
                                            <div class="form-check mb-1">
                                                <label class="form-check-label">
                                                    <input type="checkbox" 
                                                           name="crud_permissions[{{ $mainMenuKey }}][delete]" 
                                                           value="1"
                                                           class="form-check-input-styled-primary"
                                                           @checked($mainCanDelete)
                                                           data-fouc>
                                                    <span class="ml-2"><i class="icon-trash text-danger"></i> Eliminar</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <input type="hidden" name="crud_permissions[{{ $mainMenuKey }}][create]" value="0">
                                    <input type="hidden" name="crud_permissions[{{ $mainMenuKey }}][edit]" value="0">
                                    <input type="hidden" name="crud_permissions[{{ $mainMenuKey }}][delete]" value="0">
                                @endif
                            </div>
                            @if (isset($mainMenu['submenus']) && count($mainMenu['submenus']) > 0)
                                <div class="card-body">
                                    <div class="row">
                                        @foreach ($mainMenu['submenus'] as $subMenu)
                                            @php
                                                $subMenuKey = $subMenu['key'];
                                                $subPermission = $permissions[$subMenuKey] ?? null;
                                                $subPermitted = $subPermission ? $subPermission['permitted'] : true;
                                                $subCanCreate = $subPermission ? ($subPermission['can_create'] ?? true) : true;
                                                $subCanEdit = $subPermission ? ($subPermission['can_edit'] ?? true) : true;
                                                $subCanDelete = $subPermission ? ($subPermission['can_delete'] ?? true) : true;
                                            @endphp
                                            
                                            <div class="col-md-6 mb-3">
                                                <div class="form-check">
                                                    <label class="form-check-label">
                                                        @if (!$mainPermitted)
                                                            <input type="hidden" name="menu_permissions[{{ $subMenuKey }}]" value="0">
                                                        @endif
                                                        <input type="checkbox" 
                                                               name="menu_permissions[{{ $subMenuKey }}]" 
                                                               value="1"
                                                               class="form-check-input-styled-primary sub-menu-checkbox"
                                                               data-main-key="{{ $mainMenuKey }}"
                                                               @checked($subPermitted && $mainPermitted)
                                                               @if(!$mainPermitted) disabled @endif
                                                               data-fouc>
                                                        <i class="{{ $subMenu['icon'] }} mr-2"></i>
                                                        {{ $subMenu['name'] }}
                                                    </label>
                                                </div>
                                                @if ($subPermitted && $mainPermitted)
                                                    <div class="ml-4 mt-2">
                                                        <small class="text-muted font-weight-semibold d-block mb-1">CRUD:</small>
                                                        <div class="d-flex flex-column">
                                                            <div class="form-check mb-1">
                                                                <label class="form-check-label">
                                                                    <input type="checkbox" 
                                                                           name="crud_permissions[{{ $subMenuKey }}][create]" 
                                                                           value="1"
                                                                           class="form-check-input-styled-primary"
                                                                           @checked($subCanCreate)
                                                                           data-fouc>
                                                                    <span class="ml-2"><i class="icon-plus-circle2 text-success"></i> <small>Crear</small></span>
                                                                </label>
                                                            </div>
                                                            <div class="form-check mb-1">
                                                                <label class="form-check-label">
                                                                    <input type="checkbox" 
                                                                           name="crud_permissions[{{ $subMenuKey }}][edit]" 
                                                                           value="1"
                                                                           class="form-check-input-styled-primary"
                                                                           @checked($subCanEdit)
                                                                           data-fouc>
                                                                    <span class="ml-2"><i class="icon-pencil7 text-primary"></i> <small>Editar</small></span>
                                                                </label>
                                                            </div>
                                                            <div class="form-check mb-1">
                                                                <label class="form-check-label">
                                                                    <input type="checkbox" 
                                                                           name="crud_permissions[{{ $subMenuKey }}][delete]" 
                                                                           value="1"
                                                                           class="form-check-input-styled-primary"
                                                                           @checked($subCanDelete)
                                                                           data-fouc>
                                                                    <span class="ml-2"><i class="icon-trash text-danger"></i> <small>Eliminar</small></span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <input type="hidden" name="crud_permissions[{{ $subMenuKey }}][create]" value="0">
                                                    <input type="hidden" name="crud_permissions[{{ $subMenuKey }}][edit]" value="0">
                                                    <input type="hidden" name="crud_permissions[{{ $subMenuKey }}][delete]" value="0">
                                                @endif

                                                {{-- Manejar submenús anidados (como Configuración MQTT) --}}
                                                @if (isset($subMenu['submenus']) && count($subMenu['submenus']) > 0)
                                                    <div class="ml-4 mt-2">
                                                        @foreach ($subMenu['submenus'] as $nestedSubMenu)
                                                            @php
                                                                $nestedKey = $nestedSubMenu['key'];
                                                                $nestedPermission = $permissions[$nestedKey] ?? null;
                                                                $nestedPermitted = $nestedPermission ? $nestedPermission['permitted'] : true;
                                                                $nestedCanCreate = $nestedPermission ? ($nestedPermission['can_create'] ?? true) : true;
                                                                $nestedCanEdit = $nestedPermission ? ($nestedPermission['can_edit'] ?? true) : true;
                                                                $nestedCanDelete = $nestedPermission ? ($nestedPermission['can_delete'] ?? true) : true;
                                                            @endphp
                                                            <div class="form-check mb-2">
                                                                <label class="form-check-label">
                                                                    @if (!$subPermitted || !$mainPermitted)
                                                                        <input type="hidden" name="menu_permissions[{{ $nestedKey }}]" value="0">
                                                                    @endif
                                                                    <input type="checkbox" 
                                                                           name="menu_permissions[{{ $nestedKey }}]" 
                                                                           value="1"
                                                                           class="form-check-input-styled-primary nested-sub-menu-checkbox"
                                                                           data-main-key="{{ $mainMenuKey }}"
                                                                           data-parent-key="{{ $subMenuKey }}"
                                                                           @checked($nestedPermitted && $subPermitted && $mainPermitted)
                                                                           @if(!$subPermitted || !$mainPermitted) disabled @endif
                                                                           data-fouc>
                                                                    <i class="{{ $nestedSubMenu['icon'] }} mr-2"></i>
                                                                    {{ $nestedSubMenu['name'] }}
                                                                </label>
                                                            </div>
                                                            @if ($nestedPermitted && $subPermitted && $mainPermitted)
                                                                <div class="ml-4 mb-2">
                                                                    <small class="text-muted font-weight-semibold d-block mb-1">CRUD:</small>
                                                                    <div class="d-flex flex-column">
                                                                        <div class="form-check mb-1">
                                                                            <label class="form-check-label">
                                                                                <input type="checkbox" 
                                                                                       name="crud_permissions[{{ $nestedKey }}][create]" 
                                                                                       value="1"
                                                                                       class="form-check-input-styled-primary"
                                                                                       @checked($nestedCanCreate)
                                                                                       data-fouc>
                                                                                <span class="ml-2"><i class="icon-plus-circle2 text-success"></i> <small>Crear</small></span>
                                                                            </label>
                                                                        </div>
                                                                        <div class="form-check mb-1">
                                                                            <label class="form-check-label">
                                                                                <input type="checkbox" 
                                                                                       name="crud_permissions[{{ $nestedKey }}][edit]" 
                                                                                       value="1"
                                                                                       class="form-check-input-styled-primary"
                                                                                       @checked($nestedCanEdit)
                                                                                       data-fouc>
                                                                                <span class="ml-2"><i class="icon-pencil7 text-primary"></i> <small>Editar</small></span>
                                                                            </label>
                                                                        </div>
                                                                        <div class="form-check mb-1">
                                                                            <label class="form-check-label">
                                                                                <input type="checkbox" 
                                                                                       name="crud_permissions[{{ $nestedKey }}][delete]" 
                                                                                       value="1"
                                                                                       class="form-check-input-styled-primary"
                                                                                       @checked($nestedCanDelete)
                                                                                       data-fouc>
                                                                                <span class="ml-2"><i class="icon-trash text-danger"></i> <small>Eliminar</small></span>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @else
                                                                <input type="hidden" name="crud_permissions[{{ $nestedKey }}][create]" value="0">
                                                                <input type="hidden" name="crud_permissions[{{ $nestedKey }}][edit]" value="0">
                                                                <input type="hidden" name="crud_permissions[{{ $nestedKey }}][delete]" value="0">
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </fieldset>

                <div class="text-right">
                    <button type="submit" class="btn btn-primary">Actualizar permisos <i
                            class="icon-paperplane ml-2"></i></button>
                </div>
            </form>
        </div>
    </div>
    <!-- /form inputs -->
@endsection

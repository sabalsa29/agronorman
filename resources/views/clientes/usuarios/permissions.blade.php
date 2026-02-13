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

    function refreshStyled($el) {
        // Uniform (checkboxes)
        if (typeof $ !== 'undefined' && $.uniform && typeof $.uniform.update === 'function') {
            $.uniform.update($el);
        }
    }

    function extractKeyFromName(name) {
        const m = (name || '').match(/\[(.*?)\]/);
        return m ? m[1] : null;
    }

    function setMenuCheckboxState($cb, {checked = null, disabled = null} = {}) {
        if (checked !== null) $cb.prop('checked', !!checked);
        if (disabled !== null) $cb.prop('disabled', !!disabled);
        refreshStyled($cb);
    }

    function setCrudState(menuKey, $container, {enabled, checkAll = false, keepAsIs = false} = {}) {
        const actions = ['create', 'edit', 'delete'];

        actions.forEach(function(action) {
            const selector = `input[type="checkbox"][name="crud_permissions[${menuKey}][${action}]"]`;
            const $crud = $container.find(selector);

            if (!$crud.length) return;

            $crud.prop('disabled', !enabled);

            if (!enabled) {
                $crud.prop('checked', false);
            } else {
                if (checkAll) $crud.prop('checked', true);
                if (keepAsIs) {
                    // no tocar checked
                }
            }

            refreshStyled($crud);
        });
    }

    function disableNestedUnderSub($subContainer, mainKey, subKey) {
        const $nested = $subContainer.find(`.nested-sub-menu-checkbox[data-main-key="${mainKey}"][data-parent-key="${subKey}"]`);
        $nested.each(function() {
            const $n = $(this);
            const nestedKey = extractKeyFromName($n.attr('name'));
            setMenuCheckboxState($n, {checked: false, disabled: true});
            if (nestedKey) setCrudState(nestedKey, $subContainer, {enabled: false});
        });
    }

    function enableNestedUnderSub($subContainer, mainKey, subKey) {
        const $nested = $subContainer.find(`.nested-sub-menu-checkbox[data-main-key="${mainKey}"][data-parent-key="${subKey}"]`);
        $nested.each(function() {
            const $n = $(this);
            const nestedKey = extractKeyFromName($n.attr('name'));

            setMenuCheckboxState($n, {disabled: false}); // no forzamos checked
            if (nestedKey) {
                if ($n.is(':checked')) {
                    // si ya está marcado, habilitar CRUD sin forzar checkAll
                    setCrudState(nestedKey, $subContainer, {enabled: true, keepAsIs: true});
                } else {
                    // si no está marcado, CRUD apagado
                    setCrudState(nestedKey, $subContainer, {enabled: false});
                }
            }
        });
    }

    // =========================
    //  EVENTOS
    // =========================

    // MAIN MENU toggle
    $(document).on('change', '.main-menu-checkbox', function() {
        const $main = $(this);
        const mainKey = $main.data('main-key');
        const isChecked = $main.is(':checked');
        const $mainCard = $main.closest('.card');

        if (isChecked) {
            // ✅ Al marcar main: CRUD main se marca completo
            setCrudState(mainKey, $mainCard, {enabled: true, checkAll: true});

            // Habilitar submenús
            $mainCard.find(`.sub-menu-checkbox[data-main-key="${mainKey}"]`).each(function() {
                const $sub = $(this);
                const subKey = extractKeyFromName($sub.attr('name'));
                const $subContainer = $sub.closest('.col-md-6');

                setMenuCheckboxState($sub, {disabled: false}); // no forzamos checked

                if (subKey) {
                    if ($sub.is(':checked')) {
                        // si ya estaba marcado, CRUD se habilita (sin forzar checkAll)
                        setCrudState(subKey, $subContainer, {enabled: true, keepAsIs: true});
                        enableNestedUnderSub($subContainer, mainKey, subKey);
                    } else {
                        setCrudState(subKey, $subContainer, {enabled: false});
                        disableNestedUnderSub($subContainer, mainKey, subKey);
                    }
                }
            });

        } else {
            // ❌ Al desmarcar main: desactivar y desmarcar CRUD main
            setCrudState(mainKey, $mainCard, {enabled: false});

            // Deshabilitar submenús y todo lo de abajo
            $mainCard.find(`.sub-menu-checkbox[data-main-key="${mainKey}"]`).each(function() {
                const $sub = $(this);
                const subKey = extractKeyFromName($sub.attr('name'));
                const $subContainer = $sub.closest('.col-md-6');

                setMenuCheckboxState($sub, {checked: false, disabled: true});

                if (subKey) {
                    setCrudState(subKey, $subContainer, {enabled: false});
                    disableNestedUnderSub($subContainer, mainKey, subKey);
                }
            });
        }
    });

    // SUB MENU toggle
    $(document).on('change', '.sub-menu-checkbox', function() {
        const $sub = $(this);
        const mainKey = $sub.data('main-key');
        const subKey = extractKeyFromName($sub.attr('name'));
        const $subContainer = $sub.closest('.col-md-6');
        const isChecked = $sub.is(':checked');

        if (!subKey) return;

        if (isChecked) {
            // ✅ Al marcar sub: CRUD sub se marca completo
            setCrudState(subKey, $subContainer, {enabled: true, checkAll: true});
            // habilitar nested (sin auto-check)
            enableNestedUnderSub($subContainer, mainKey, subKey);
        } else {
            // ❌ Al desmarcar sub: desactivar CRUD y nested
            setCrudState(subKey, $subContainer, {enabled: false});
            disableNestedUnderSub($subContainer, mainKey, subKey);
        }
    });

    // NESTED toggle
    $(document).on('change', '.nested-sub-menu-checkbox', function() {
        const $nested = $(this);
        const nestedKey = extractKeyFromName($nested.attr('name'));
        const $subContainer = $nested.closest('.col-md-6');
        const isChecked = $nested.is(':checked');

        if (!nestedKey) return;

        if (isChecked) {
            // ✅ Al marcar nested: CRUD nested se marca completo
            setCrudState(nestedKey, $subContainer, {enabled: true, checkAll: true});
        } else {
            setCrudState(nestedKey, $subContainer, {enabled: false});
        }
    });

    // =========================
    //  SYNC INICIAL (SIN forzar checkAll)
    // =========================
    function syncInitialState() {
        $('.main-menu-checkbox').each(function() {
            const $main = $(this);
            const mainKey = $main.data('main-key');
            const $mainCard = $main.closest('.card');
            const mainChecked = $main.is(':checked');

            // main CRUD: habilitar/inhabilitar sin forzar checks
            setCrudState(mainKey, $mainCard, {enabled: mainChecked, keepAsIs: true});

            $mainCard.find(`.sub-menu-checkbox[data-main-key="${mainKey}"]`).each(function() {
                const $sub = $(this);
                const subKey = extractKeyFromName($sub.attr('name'));
                const $subContainer = $sub.closest('.col-md-6');

                // sub checkbox depende del main
                setMenuCheckboxState($sub, {disabled: !mainChecked});

                if (!subKey) return;

                const subChecked = mainChecked && $sub.is(':checked');

                // sub CRUD depende de subChecked (no forzar checkAll)
                setCrudState(subKey, $subContainer, {enabled: subChecked, keepAsIs: true});

                if (subChecked) {
                    enableNestedUnderSub($subContainer, mainKey, subKey);
                } else {
                    disableNestedUnderSub($subContainer, mainKey, subKey);
                }
            });
        });
    }

    syncInitialState();
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

                <!-- Permisos de Menú -->
                <fieldset class="mb-4">
                    <legend class="text-uppercase font-size-sm font-weight-bold">Permisos del Sidebar</legend>
                    <p class="text-muted mb-3">
                        Seleccione los menús principales y secundarios que el usuario puede ver en el sidebar.
                    </p>

                    @foreach ($menuStructure as $mainMenu)
                        @php
                            $mainMenuKey = $mainMenu['key'];
                            $mainPermission = $permissions[$mainMenuKey] ?? null;
                            $mainPermitted = $mainPermission ? $mainPermission['permitted'] : true;
                            $mainCanCreate = $mainPermission ? ($mainPermission['can_create'] ?? true) : true;
                            $mainCanEdit   = $mainPermission ? ($mainPermission['can_edit'] ?? true) : true;
                            $mainCanDelete = $mainPermission ? ($mainPermission['can_delete'] ?? true) : true;
                        @endphp

                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <div class="form-check">
                                    <label class="form-check-label font-weight-semibold">
                                        {{-- Hidden 0 para que al desmarcar envíe 0 --}}
                                        <input type="hidden" name="menu_permissions[{{ $mainMenuKey }}]" value="0">

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

                                {{-- CRUD MAIN (siempre visible) --}}
                                <div class="mt-2 ml-4">
                                    <small class="text-muted font-weight-semibold d-block mb-2">CRUD:</small>
                                    <div class="d-flex flex-column">

                                        <div class="form-check mb-1">
                                            <label class="form-check-label">
                                                <input type="hidden" name="crud_permissions[{{ $mainMenuKey }}][create]" value="0">
                                                <input type="checkbox"
                                                       name="crud_permissions[{{ $mainMenuKey }}][create]"
                                                       value="1"
                                                       class="form-check-input-styled-primary"
                                                       @checked($mainPermitted && $mainCanCreate)
                                                       @if(!$mainPermitted) disabled @endif
                                                       data-fouc>
                                                <span class="ml-2"><i class="icon-plus-circle2 text-success"></i> Crear</span>
                                            </label>
                                        </div>

                                        <div class="form-check mb-1">
                                            <label class="form-check-label">
                                                <input type="hidden" name="crud_permissions[{{ $mainMenuKey }}][edit]" value="0">
                                                <input type="checkbox"
                                                       name="crud_permissions[{{ $mainMenuKey }}][edit]"
                                                       value="1"
                                                       class="form-check-input-styled-primary"
                                                       @checked($mainPermitted && $mainCanEdit)
                                                       @if(!$mainPermitted) disabled @endif
                                                       data-fouc>
                                                <span class="ml-2"><i class="icon-pencil7 text-primary"></i> Editar</span>
                                            </label>
                                        </div>

                                        <div class="form-check mb-1">
                                            <label class="form-check-label">
                                                <input type="hidden" name="crud_permissions[{{ $mainMenuKey }}][delete]" value="0">
                                                <input type="checkbox"
                                                       name="crud_permissions[{{ $mainMenuKey }}][delete]"
                                                       value="1"
                                                       class="form-check-input-styled-primary"
                                                       @checked($mainPermitted && $mainCanDelete)
                                                       @if(!$mainPermitted) disabled @endif
                                                       data-fouc>
                                                <span class="ml-2"><i class="icon-trash text-danger"></i> Eliminar</span>
                                            </label>
                                        </div>

                                    </div>
                                </div>
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
                                                $subCanEdit   = $subPermission ? ($subPermission['can_edit'] ?? true) : true;
                                                $subCanDelete = $subPermission ? ($subPermission['can_delete'] ?? true) : true;

                                                $subChecked = ($subPermitted && $mainPermitted);
                                            @endphp

                                            <div class="col-md-6 mb-3">
                                                <div class="form-check">
                                                    <label class="form-check-label">
                                                        <input type="hidden" name="menu_permissions[{{ $subMenuKey }}]" value="0">

                                                        <input type="checkbox"
                                                               name="menu_permissions[{{ $subMenuKey }}]"
                                                               value="1"
                                                               class="form-check-input-styled-primary sub-menu-checkbox"
                                                               data-main-key="{{ $mainMenuKey }}"
                                                               @checked($subChecked)
                                                               @if(!$mainPermitted) disabled @endif
                                                               data-fouc>

                                                        <i class="{{ $subMenu['icon'] }} mr-2"></i>
                                                        {{ $subMenu['name'] }}
                                                    </label>
                                                </div>

                                                {{-- CRUD SUB (siempre visible) --}}
                                                <div class="ml-4 mt-2">
                                                    <small class="text-muted font-weight-semibold d-block mb-1">CRUD:</small>
                                                    <div class="d-flex flex-column">

                                                        <div class="form-check mb-1">
                                                            <label class="form-check-label">
                                                                <input type="hidden" name="crud_permissions[{{ $subMenuKey }}][create]" value="0">
                                                                <input type="checkbox"
                                                                       name="crud_permissions[{{ $subMenuKey }}][create]"
                                                                       value="1"
                                                                       class="form-check-input-styled-primary"
                                                                       @checked($subChecked && $subCanCreate)
                                                                       @if(!$subChecked) disabled @endif
                                                                       data-fouc>
                                                                <span class="ml-2"><i class="icon-plus-circle2 text-success"></i> <small>Crear</small></span>
                                                            </label>
                                                        </div>

                                                        <div class="form-check mb-1">
                                                            <label class="form-check-label">
                                                                <input type="hidden" name="crud_permissions[{{ $subMenuKey }}][edit]" value="0">
                                                                <input type="checkbox"
                                                                       name="crud_permissions[{{ $subMenuKey }}][edit]"
                                                                       value="1"
                                                                       class="form-check-input-styled-primary"
                                                                       @checked($subChecked && $subCanEdit)
                                                                       @if(!$subChecked) disabled @endif
                                                                       data-fouc>
                                                                <span class="ml-2"><i class="icon-pencil7 text-primary"></i> <small>Editar</small></span>
                                                            </label>
                                                        </div>

                                                        <div class="form-check mb-1">
                                                            <label class="form-check-label">
                                                                <input type="hidden" name="crud_permissions[{{ $subMenuKey }}][delete]" value="0">
                                                                <input type="checkbox"
                                                                       name="crud_permissions[{{ $subMenuKey }}][delete]"
                                                                       value="1"
                                                                       class="form-check-input-styled-primary"
                                                                       @checked($subChecked && $subCanDelete)
                                                                       @if(!$subChecked) disabled @endif
                                                                       data-fouc>
                                                                <span class="ml-2"><i class="icon-trash text-danger"></i> <small>Eliminar</small></span>
                                                            </label>
                                                        </div>

                                                    </div>
                                                </div>

                                                {{-- NESTED --}}
                                                @if (isset($subMenu['submenus']) && count($subMenu['submenus']) > 0)
                                                    <div class="ml-4 mt-2">
                                                        @foreach ($subMenu['submenus'] as $nestedSubMenu)
                                                            @php
                                                                $nestedKey = $nestedSubMenu['key'];
                                                                $nestedPermission = $permissions[$nestedKey] ?? null;
                                                                $nestedPermitted = $nestedPermission ? $nestedPermission['permitted'] : true;
                                                                $nestedCanCreate = $nestedPermission ? ($nestedPermission['can_create'] ?? true) : true;
                                                                $nestedCanEdit   = $nestedPermission ? ($nestedPermission['can_edit'] ?? true) : true;
                                                                $nestedCanDelete = $nestedPermission ? ($nestedPermission['can_delete'] ?? true) : true;

                                                                $nestedChecked = ($nestedPermitted && $subChecked);
                                                            @endphp

                                                            <div class="form-check mb-2">
                                                                <label class="form-check-label">
                                                                    <input type="hidden" name="menu_permissions[{{ $nestedKey }}]" value="0">

                                                                    <input type="checkbox"
                                                                           name="menu_permissions[{{ $nestedKey }}]"
                                                                           value="1"
                                                                           class="form-check-input-styled-primary nested-sub-menu-checkbox"
                                                                           data-main-key="{{ $mainMenuKey }}"
                                                                           data-parent-key="{{ $subMenuKey }}"
                                                                           @checked($nestedChecked)
                                                                           @if(!$subChecked) disabled @endif
                                                                           data-fouc>

                                                                    <i class="{{ $nestedSubMenu['icon'] }} mr-2"></i>
                                                                    {{ $nestedSubMenu['name'] }}
                                                                </label>
                                                            </div>

                                                            {{-- CRUD NESTED (siempre visible) --}}
                                                            <div class="ml-4 mb-2">
                                                                <small class="text-muted font-weight-semibold d-block mb-1">CRUD:</small>
                                                                <div class="d-flex flex-column">

                                                                    <div class="form-check mb-1">
                                                                        <label class="form-check-label">
                                                                            <input type="hidden" name="crud_permissions[{{ $nestedKey }}][create]" value="0">
                                                                            <input type="checkbox"
                                                                                   name="crud_permissions[{{ $nestedKey }}][create]"
                                                                                   value="1"
                                                                                   class="form-check-input-styled-primary"
                                                                                   @checked($nestedChecked && $nestedCanCreate)
                                                                                   @if(!$nestedChecked) disabled @endif
                                                                                   data-fouc>
                                                                            <span class="ml-2"><i class="icon-plus-circle2 text-success"></i> <small>Crear</small></span>
                                                                        </label>
                                                                    </div>

                                                                    <div class="form-check mb-1">
                                                                        <label class="form-check-label">
                                                                            <input type="hidden" name="crud_permissions[{{ $nestedKey }}][edit]" value="0">
                                                                            <input type="checkbox"
                                                                                   name="crud_permissions[{{ $nestedKey }}][edit]"
                                                                                   value="1"
                                                                                   class="form-check-input-styled-primary"
                                                                                   @checked($nestedChecked && $nestedCanEdit)
                                                                                   @if(!$nestedChecked) disabled @endif
                                                                                   data-fouc>
                                                                            <span class="ml-2"><i class="icon-pencil7 text-primary"></i> <small>Editar</small></span>
                                                                        </label>
                                                                    </div>

                                                                    <div class="form-check mb-1">
                                                                        <label class="form-check-label">
                                                                            <input type="hidden" name="crud_permissions[{{ $nestedKey }}][delete]" value="0">
                                                                            <input type="checkbox"
                                                                                   name="crud_permissions[{{ $nestedKey }}][delete]"
                                                                                   value="1"
                                                                                   class="form-check-input-styled-primary"
                                                                                   @checked($nestedChecked && $nestedCanDelete)
                                                                                   @if(!$nestedChecked) disabled @endif
                                                                                   data-fouc>
                                                                            <span class="ml-2"><i class="icon-trash text-danger"></i> <small>Eliminar</small></span>
                                                                        </label>
                                                                    </div>

                                                                </div>
                                                            </div>
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
                    <button type="submit" class="btn btn-primary">
                        Actualizar permisos <i class="icon-paperplane ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- /form inputs -->
@endsection

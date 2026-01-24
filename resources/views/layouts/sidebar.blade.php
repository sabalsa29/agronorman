<div class="sidebar sidebar-dark sidebar-main sidebar-expand-md">

    <!-- Sidebar mobile toggler -->
    <div class="sidebar-mobile-toggler text-center">
        <a href="#" class="sidebar-mobile-main-toggle">
            <i class="icon-arrow-left8"></i>
        </a>
        Navigation
        <a href="#" class="sidebar-mobile-expand">
            <i class="icon-screen-full"></i>
            <i class="icon-screen-normal"></i>
        </a>
    </div>
    <!-- /sidebar mobile toggler -->


    <!-- Sidebar content -->
    <div class="sidebar-content">

        <!-- User menu -->
        <div class="sidebar-user">
            <div class="card-body">
                <div class="media">
                    <div class="mr-3">
                        <a href="#"><img src="{{ url('assets/images/perfil.png') }}" width="38" height="38"
                                class="rounded-circle" alt=""></a>
                    </div>

                    <div class="media-body">
                        @if (Auth::check())
                            <div class="media-title font-weight-semibold">{{ Auth::user()->name }}</div>
                            <div class="font-size-xs opacity-50">
                                <span class="badge bg-green-400 align-self-center ml-auto">Activo</span>
                            </div>
                        @else
                            <div class="media-title font-weight-semibold">Sin sesión</div>
                            <div class="font-size-xs opacity-50">
                                <span class="badge bg-red-400 align-self-center ml-auto">Inactivo</span>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
        <!-- /user menu -->


        <!-- Main navigation -->
        <div class="card card-sidebar-mobile">
            <ul class="nav nav-sidebar" data-nav-type="accordion">

                <!-- Main -->
                <li class="nav-item-header">
                    <div class="text-uppercase font-size-xs line-height-xs">SECCIONES</div> <i class="icon-menu"
                        title="SECCIONES"></i>
                </li>
                <li
                    class="nav-item {{ isActiveSection(['grupos.zonas-manejo']) ? 'nav-item-expanded nav-item-open' : '' }}">
                    <a href="{{ route('grupos.zonas-manejo') }}" class="nav-link">
                        <i class="icon-home4"></i>
                        <span>
                            Plataforma de inteligencia agronómica
                        </span>
                    </a>
                </li>
  
                @if (Auth::check() && Auth::user()->hasMenuPermission('usuarios'))
                    <li
                        class="nav-item nav-item-submenu {{ isActiveSection(['clientes.index, grupos.index']) ? 'nav-item-expanded nav-item-open' : '' }}">
                        <a href="#" class="nav-link"><i class="icon-user-tie"></i> <span>Usuarios</span></a>

                        <ul class="nav nav-group-sub" data-submenu-title="Layouts">
                            @if (Auth::check() && Auth::user()->hasSubMenuPermission('usuarios', 'usuarios.clientes'))
                                <li class="nav-item"><a href="{{ route('clientes.index') }}"
                                        class="nav-link {{ Route::is('clientes.index') ? 'active' : '' }}"><i
                                            class="icon-user-tie"></i> Productores</a>
                                </li>
                            @endif
                            @if (Auth::check() )
                                <li class="nav-item"><a href="{{ route('usuarios.index') }}"
                                        class="nav-link {{ Route::is('usuarios.index') ? 'active' : '' }}"><i
                                            class="icon-user-tie"></i> Usuarios</a>
                                </li>
                            @endif
                            @if (Auth::check() && Auth::user()->hasSubMenuPermission('usuarios', 'usuarios.grupos'))
                                <li class="nav-item"><a href="{{ route('grupos.index') }}"
                                        class="nav-link {{ Route::is('grupos.index') ? 'active' : '' }}"><i
                                            class="icon-collaboration"></i> Grupos</a>
                                </li>
                            @endif
                            @if (Auth::check() && Auth::user()->isSuperAdmin())
                                <li class="nav-item"><a href="{{ route('platform-logs.index') }}"
                                        class="nav-link {{ Route::is('platform-logs.*') ? 'active' : '' }}"><i
                                            class="icon-file-text2"></i> Logs de la Plataforma</a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                @if (Auth::check() && Auth::user()->hasMenuPermission('estaciones'))
                    <li
                        class="nav-item nav-item-submenu {{ isActiveSection(['almacenes.index', 'fabricantes.index', 'tipo_estacion.index', 'grupos.index', 'estaciones.index']) ? 'nav-item-expanded nav-item-open' : '' }}">
                        <a href="#" class="nav-link"><i class="icon-station"></i> <span>Estaciones de
                                medición</span></a>

                        <ul class="nav nav-group-sub" data-submenu-title="Layouts">
                            @if (Auth::check() && Auth::user()->hasSubMenuPermission('estaciones', 'estaciones.fabricantes'))
                                <li class="nav-item"><a href="{{ route('fabricantes.index') }}"
                                        class="nav-link {{ Route::is('fabricantes.index') ? 'active' : '' }}"><i
                                            class="icon-wrench3"></i> Fabricantes</a>
                                </li>
                            @endif
                            @if (Auth::check() && Auth::user()->hasSubMenuPermission('estaciones', 'estaciones.tipo_estacion'))
                                <li class="nav-item"><a href="{{ route('tipo_estacion.index') }}"
                                        class="nav-link {{ Route::is('tipo_estacion.index') ? 'active' : '' }}"><i
                                            class="icon-satellite-dish2"></i> Tipos de estaciones</a>
                                </li>
                            @endif
                            @if (Auth::check() && Auth::user()->hasSubMenuPermission('estaciones', 'estaciones.grupos'))
                                <li class="nav-item"><a href="{{ route('grupos.index') }}"
                                        class="nav-link {{ Route::is('grupos.index') ? 'active' : '' }}"><i
                                            class="icon-collaboration"></i> Grupos</a>
                                </li>
                                <li class="nav-item"><a href="{{ route('grupos.zonas-manejo') }}"
                                        class="nav-link {{ Route::is('grupos.zonas-manejo') ? 'active' : '' }}"><i
                                            class="icon-location4"></i> Mis Zonas de Manejo</a>
                                </li>
                            @endif
                            @if (Auth::check() && Auth::user()->hasSubMenuPermission('estaciones', 'estaciones.almacenes'))
                                <li class="nav-item"><a href="{{ route('almacenes.index') }}"
                                        class="nav-link {{ Route::is('almacenes.index') ? 'active' : '' }}"><i
                                            class="icon-home7"></i> Almacenes</a>
                                </li>
                            @endif
                            @if (Auth::check() && Auth::user()->hasSubMenuPermission('estaciones', 'estaciones.alta'))
                                <li class="nav-item"><a href="{{ route('estaciones.index') }}"
                                        class="nav-link {{ Route::is('estaciones.index') ? 'active' : '' }}"><i
                                            class="icon-station"></i> Alta de estaciones <i class="icon-plus22"></i></a>
                                </li>
                            @endif
                            @if (Auth::check() && Auth::user()->hasSubMenuPermission('estaciones', 'estaciones.configuracion_mqtt'))
                                <li
                                    class="nav-item nav-item-submenu {{ Route::is('configuracion-mqtt.*') ? 'nav-item-expanded nav-item-open' : '' }}">
                                    <a href="#" class="nav-link"><i class="icon-cog3"></i> <span>Configuración
                                            MQTT</span></a>
                                    <ul class="nav nav-group-sub" data-submenu-title="Configuración MQTT">
                                        @if (Auth::check() &&
                                                Auth::user()->hasSubMenuPermission(
                                                    'estaciones.configuracion_mqtt',
                                                    'estaciones.configuracion_mqtt.configuracion'))
                                            <li class="nav-item"><a href="{{ route('configuracion-mqtt.login') }}"
                                                    class="nav-link {{ Route::is('configuracion-mqtt.login') || Route::is('configuracion-mqtt.index') ? 'active' : '' }}"><i
                                                        class="icon-cog3"></i> Configuración</a>
                                            </li>
                                        @endif
                                        @if (Auth::check() &&
                                                Auth::user()->hasSubMenuPermission('estaciones.configuracion_mqtt', 'estaciones.configuracion_mqtt.usuarios'))
                                            <li class="nav-item"><a
                                                    href="{{ route('configuracion-mqtt.usuarios.index') }}"
                                                    class="nav-link {{ Route::is('configuracion-mqtt.usuarios.*') ? 'active' : '' }}"><i
                                                        class="icon-users"></i> Usuarios</a>
                                            </li>
                                        @endif
                                        @if (Auth::check() &&
                                                Auth::user()->hasSubMenuPermission('estaciones.configuracion_mqtt', 'estaciones.configuracion_mqtt.logs'))
                                            <li class="nav-item"><a href="{{ route('configuracion-mqtt.logs') }}"
                                                    class="nav-link {{ Route::is('configuracion-mqtt.logs') ? 'active' : '' }}"><i
                                                        class="icon-list"></i> Logs</a>
                                            </li>
                                        @endif
                                    </ul>
                                </li>
                            @endif

                        </ul>
                    </li>
                @endif
                @if (Auth::check() && Auth::user()->hasMenuPermission('parametros'))
                    <li
                        class="nav-item nav-item-submenu {{ isActiveSection(['etapasfenologicas.index', 'plaga.index', 'cultivos.index', 'textura-suelo.index', 'enfermedades.index']) ? 'nav-item-expanded nav-item-open' : '' }}">
                        <a href="#" class="nav-link"><i class="icon-stats-dots"></i> <span>Parámetros
                                agronómicos</span></a>

                        <ul class="nav nav-group-sub" data-submenu-title="Layouts">
                            @if (Auth::check() && Auth::user()->hasSubMenuPermission('parametros', 'parametros.etapas_fenologicas'))
                                <li class="nav-item"><a href="{{ route('etapasfenologicas.index') }}"
                                        class="nav-link {{ Route::is('etapasfenologicas.index') ? 'active' : '' }}"><i
                                            class="icon-sun3"></i> Etapas fenológicas</a>
                                </li>
                            @endif
                            @if (Auth::check() && Auth::user()->hasSubMenuPermission('parametros', 'parametros.plagas'))
                                <li class="nav-item"><a href="{{ route('plaga.index') }}"
                                        class="nav-link {{ Route::is('plaga.index') ? 'active' : '' }}"><i
                                            class="icon-bug2"></i> Plagas</a>
                                </li>
                            @endif
                            @if (Auth::check() && Auth::user()->hasSubMenuPermission('parametros', 'parametros.cultivos'))
                                <li class="nav-item"><a href="{{ route('cultivos.index') }}"
                                        class="nav-link {{ Route::is('cultivos.index') ? 'active' : '' }}"><i
                                            class="icon-fan"></i> Cultivos</a>
                                </li>
                            @endif
                            @if (Auth::check() && Auth::user()->hasSubMenuPermission('parametros', 'parametros.textura_suelo'))
                                <li class="nav-item"><a href="{{ route('textura-suelo.index') }}"
                                        class="nav-link {{ Route::is('textura-suelo.index') ? 'active' : '' }}"><i
                                            class="icon-cube4"></i> Textura de suelo</a>
                                </li>
                            @endif
                            @if (Auth::check() && Auth::user()->hasSubMenuPermission('parametros', 'parametros.enfermedades'))
                                <li class="nav-item"><a href="{{ route('enfermedades.index') }}"
                                        class="nav-link {{ Route::is('enfermedades.index') ? 'active' : '' }}"><i
                                            class="icon-aid-kit"></i> Enfermedades</a>
                                </li>
                            @endif
                        </ul>
                    </li>
                    <li
                        class="nav-item nav-item-submenu {{ isActiveSection([
                            'accesos.parcelas.index', 'accesos.zonas.index', 'accesos.usuarios.index', 'accesos.usuarios.show', 'accesos.auditoria.index'
                        ]) ? 'nav-item-expanded nav-item-open' : '' }}">
                        <a href="#" class="nav-link">
                            <i class="icon-lock2"></i> <span>Accesos</span>
                        </a>

                        <ul class="nav nav-group-sub" data-submenu-title="Accesos">

                            @if (Auth::check() && Auth::user()->hasSubMenuPermission('accesos', 'accesos.parcelas'))
                                <li class="nav-item">
                                    <a href="{{ route('accesos.parcelas.index') }}"
                                        class="nav-link {{ Route::is('accesos.parcelas.index') ? 'active' : '' }}">
                                        <i class="icon-map5"></i> Asignar parcelas a grupos
                                    </a>
                                </li>
                            @endif

                            @if (Auth::check() && Auth::user()->hasSubMenuPermission('accesos', 'accesos.zonas'))
                                <li class="nav-item">
                                    <a href="{{ route('accesos.zonas.index') }}"
                                        class="nav-link {{ Route::is('accesos.zonas.index') ? 'active' : '' }}">
                                        <i class="icon-grid6"></i> Asignar zonas a grupos
                                    </a>
                                </li>
                            @endif

                             @if (Auth::check() && Auth::user()->hasSubMenuPermission('accesos', 'accesos.usuarios'))
                                <li class="nav-item">
                                    <a href="{{ route('accesos.usuarios.index') }}"
                                        class="nav-link {{ Route::is('accesos.usuarios.index') || Route::is('accesos.usuarios.show') ? 'active' : '' }}">
                                        <i class="icon-user-lock"></i> Acceso por usuario
                                    </a>
                                </li>
                            @endif 

                            {{--  @if (Auth::check() && Auth::user()->hasSubMenuPermission('accesos', 'accesos.auditoria'))
                                <li class="nav-item">
                                    <a href="{{ route('accesos.auditoria.index') }}"
                                        class="nav-link {{ Route::is('accesos.auditoria.index') ? 'active' : '' }}">
                                        <i class="icon-clipboard3"></i> Auditoría de accesos
                                    </a>
                                </li>
                            @endif  --}}

                        </ul>
                    </li>

                @endif
                <!-- /main -->

            </ul>
        </div>
        <!-- /main navigation -->

    </div>
    <!-- /sidebar content -->

</div>

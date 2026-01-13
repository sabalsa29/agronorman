<div class="navbar navbar-expand-md navbar-dark">
    <div class="navbar-brand">
        <a href="" class="d-inline-block">
            <img src="{{ url('assets/images/logo.png') }}" style="width: 200px; height: auto !important;" alt="">
        </a>
    </div>

    <div class="d-md-none">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-mobile">
            <i class="icon-tree5"></i>
        </button>
        <button class="navbar-toggler sidebar-mobile-main-toggle" type="button">
            <i class="icon-paragraph-justify3"></i>
        </button>
    </div>

    <div class="collapse navbar-collapse" id="navbar-mobile">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a href="#" class="navbar-nav-link sidebar-control sidebar-main-toggle d-none d-md-block">
                    <i class="icon-paragraph-justify3"></i>
                </a>
            </li>
        </ul>

        <span class="navbar-text ml-md-3 mr-md-auto">
            @if (Auth::check())
                @php
                    $user = Auth::user();
                    $roleName = $user->role ? $user->role->nombre : 'Sin rol';
                    $userName = $user->nombre ?? ($user->name ?? 'Usuario');
                @endphp
                <span class="badge bg-primary mr-2">{{ $roleName }}</span>
            @else
                <span class="badge bg-success">Online</span>
            @endif
        </span>

        <ul class="navbar-nav">

            <li class="nav-item dropdown dropdown-user">
                <a href="#" class="navbar-nav-link dropdown-toggle" data-toggle="dropdown">
                    <img src="{{ url('assets/images/perfil.png') }}" class="rounded-circle" alt="">
                    @if (Auth::check())
                        <span>{{ Auth::user()->nombre ?? (Auth::user()->name ?? 'Usuario') }}</span>
                    @else
                        <span>Sin sesión</span>
                    @endif
                </a>

                <div class="dropdown-menu dropdown-menu-right">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="icon-switch2"></i> Cerrar sesión
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </div>
</div>

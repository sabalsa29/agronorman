@php
    $prefix = '';
    $connector = '';

    if ($level > 0) {
        $connector = $isLast ? '└── ' : '├── ';
        // Agregar espacios para niveles anteriores
        for ($i = 0; $i < $level - 1; $i++) {
            $prefix .= $isLast && $i == $level - 2 ? '    ' : '│   ';
        }
        $prefix .= $connector;
    }
@endphp

<div class="tree-item tree-item-level-{{ $level }}">
    <div class="tree-item-content">
        @if ($level > 0)
            <span class="tree-prefix">{{ $prefix }}</span>
        @endif
        <span class="grupo-nombre">{{ $grupo['nombre'] }}</span>

        @if ($grupo['status'])
            <span class="grupo-badge badge-status-activo">Activo</span>
        @else
            <span class="grupo-badge badge-status-inactivo">Inactivo</span>
        @endif

        @if (!empty($grupo['usuarios']))
            <span class="usuarios-info">
                <i class="icon-user"></i>
                <span class="usuarios-list">
                    @foreach ($grupo['usuarios'] as $usuario)
                        <span class="usuario-badge" title="{{ $usuario['email'] }}">
                            {{ $usuario['nombre'] }}
                        </span>
                    @endforeach
                </span>
            </span>
        @endif

        @if (!empty($grupo['zonas_manejo']))
            <span class="zonas-info">
                <i class="icon-zone"></i>
                <span class="zonas-list">
                    @foreach ($grupo['zonas_manejo'] as $zona)
                        <span class="zona-badge">
                            {{ $zona['nombre'] }}
                        </span>
                    @endforeach
                </span>
            </span>
        @endif
    </div>

    @if (!empty($grupo['subgrupos']))
        <div class="tree-children">
            @foreach ($grupo['subgrupos'] as $index => $subgrupo)
                @include('grupos.partials.tree-item', [
                    'grupo' => $subgrupo,
                    'level' => $level + 1,
                    'isLast' => $loop->last,
                    'parentPrefix' => $prefix,
                ])
            @endforeach
        </div>
    @endif
</div>

<option value="{{ $subgrupo['id'] }}" {{ $subgrupoFiltro == $subgrupo['id'] ? 'selected' : '' }}>
    {{ trim(str_replace(['★'], '', $subgrupo['nombre_completo'])) }}
    @if ($subgrupo['zona_manejos_count'] > 0)
        → {{ $subgrupo['zona_manejos_count'] }} {{ $subgrupo['zona_manejos_count'] == 1 ? 'zona' : 'zonas' }}
    @else
        → Sin zonas
    @endif
</option>
@foreach ($subgrupo['subgrupos'] as $subsubgrupo)
    @include('grupos.partials.subgrupo-option', [
        'subgrupo' => $subsubgrupo,
        'subgrupoFiltro' => $subgrupoFiltro,
    ])
@endforeach

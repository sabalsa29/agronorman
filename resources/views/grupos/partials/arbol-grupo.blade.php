@php
    $subgrupos = collect($nodo['subgrupos'] ?? []);
    $parcelas  = collect($nodo['parcelas'] ?? []);
    $nivelPx   = ($nivel ?? 0) * 14;
@endphp

<details class="tree-node" open style="margin-left: {{ $nivelPx }}px;">
    <summary class="tree-summary">
        <div class="left">
            <i class="icon-collaboration text-muted"></i>
            <span class="tree-title">{{ $nodo['nombre'] ?? 'Grupo' }}</span>

            @if (!empty($nodo['total_zonas']))
                <span class="badge badge-primary badge-pill">
                    {{ $nodo['total_zonas'] }}
                </span>
            @endif
        </div>

        <span class="text-muted small">
            ID: {{ $nodo['id'] ?? '-' }}
        </span>
    </summary>

    <div class="tree-children">

        {{-- Subgrupos --}}
        @if ($subgrupos->isNotEmpty())
            <div class="mb-2 text-muted small">
                <i class="icon-git-branch mr-1"></i> Subgrupos
            </div>

            @foreach ($subgrupos as $sub)
                @include('grupos.partials.arbol-grupo', ['nodo' => $sub, 'nivel' => ($nivel ?? 0) + 1])
            @endforeach
        @endif

        {{-- Parcelas del grupo --}}
        @if ($parcelas->isNotEmpty())
            <div class="mt-2 mb-2 text-muted small">
                <i class="icon-map5 mr-1"></i> Parcelas
            </div>

            @foreach ($parcelas as $parcela)
                @php
                    $zonas = collect($parcela['zonas'] ?? []);
                @endphp

                <details class="tree-node" open style="margin-left: 14px;">
                    <summary class="tree-summary">
                        <div class="left">
                            <i class="icon-map5 text-muted"></i>
                            <span class="tree-title">{{ $parcela['nombre'] ?? 'Parcela' }}</span>

                            <span class="badge badge-light border">
                                {{ $zonas->count() }}
                            </span>

                            @if (!empty($parcela['cliente']))
                                <span class="text-muted small">
                                    <i class="icon-office mr-1"></i>{{ $parcela['cliente'] }}
                                </span>
                            @endif
                        </div>

                        <span class="text-muted small">
                            ID: {{ $parcela['id'] ?? '-' }}
                        </span>
                    </summary>

                    <div class="tree-children" style="margin-left: 14px;">
                        @if ($zonas->isEmpty())
                            <div class="text-muted small">Sin zonas.</div>
                        @else
                            <div class="list-group list-group-flush">
                                @foreach ($zonas as $zona)
                                    <a href="{{ route('grupos.zonas-manejo', [
                                            'cliente_id' => $zona['cliente_id'] ?? null,
                                            'parcela_id' => $zona['parcela_id'] ?? null,
                                            'zona_manejo_id' => $zona['id'],
                                            'tipo_cultivo_id' => $zona['tipo_cultivo_id'] ?? null,
                                            'etapa_fenologica_id' => $zona['etapa_fenologica_id'] ?? null,
                                            'periodo' => 1,
                                        ]) }}"
                                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center tree-zone-item">
                                        <div class="pr-3">
                                            <div class="d-flex align-items-center">
                                                <span class="tree-dot mr-2"></span>
                                                <i class="icon-location4 mr-2 text-muted"></i>
                                                <strong class="text-dark">{{ $zona['nombre'] ?? 'Zona' }}</strong>
                                            </div>

                                            @if (!empty($zona['tipo_cultivo_nombre']))
                                                <div class="small text-muted ml-4">
                                                    <i class="icon-leaf mr-1"></i>{{ $zona['tipo_cultivo_nombre'] }}
                                                </div>
                                            @endif
                                        </div>

                                        <span class="btn btn-sm btn-primary">
                                            <i class="icon-arrow-right8 mr-1"></i> Dashboard
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </details>
            @endforeach
        @endif

        @if ($subgrupos->isEmpty() && $parcelas->isEmpty())
            <div class="text-muted small">Este grupo no tiene subgrupos ni parcelas asociadas.</div>
        @endif
    </div>
</details>

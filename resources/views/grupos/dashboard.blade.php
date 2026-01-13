@extends('layouts.web')
@section('title', $section_name)
@section('ruta_home', route('grupos.index'))
@section('content')
    <div class="card">
        <div class="card-header header-elements-inline">
            <h5 class="card-title">{{ $section_name }}</h5>
            <div class="header-elements">
                <a href="{{ route('grupos.index') }}" class="btn btn-light btn-sm">
                    <i class="icon-arrow-left7 mr-2"></i> Volver a Listado
                </a>
                <div class="list-icons">
                    <a class="list-icons-item" data-action="collapse"></a>
                    <a class="list-icons-item" data-action="reload"></a>
                    <a class="list-icons-item" data-action="remove"></a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <p class="mb-4">{{ $section_description }}</p>

            <div class="tree-container" style="font-family: 'Courier New', monospace; line-height: 1.8;">
                @forelse($estructura as $grupo)
                    @include('grupos.partials.tree-item', [
                        'grupo' => $grupo,
                        'level' => 0,
                        'isLast' => $loop->last,
                    ])
                @empty
                    <div class="alert alert-info">
                        <p>No hay grupos disponibles para mostrar.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <style>
        .tree-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
        }

        .tree-item {
            margin: 8px 0;
        }

        .tree-item-content {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            background: white;
            border-left: 3px solid #007bff;
            border-radius: 4px;
            margin-left: 0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .tree-item-content:hover {
            background: #f0f0f0;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
        }

        .tree-item-level-0 .tree-item-content {
            border-left-color: #28a745;
            font-weight: bold;
            font-size: 1.1em;
        }

        .tree-item-level-1 .tree-item-content {
            border-left-color: #17a2b8;
        }

        .tree-item-level-2 .tree-item-content {
            border-left-color: #ffc107;
        }

        .tree-item-level-3 .tree-item-content {
            border-left-color: #fd7e14;
        }

        .tree-item-level-4 .tree-item-content {
            border-left-color: #e83e8c;
        }

        .tree-prefix {
            color: #6c757d;
            margin-right: 8px;
            font-weight: normal;
        }

        .grupo-nombre {
            font-weight: 600;
            color: #212529;
            margin-right: 12px;
        }

        .grupo-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: 500;
            margin-left: 8px;
        }

        .badge-status-activo {
            background: #d4edda;
            color: #155724;
        }

        .badge-status-inactivo {
            background: #f8d7da;
            color: #721c24;
        }

        .usuarios-info {
            margin-left: 12px;
            font-size: 0.9em;
            color: #6c757d;
        }

        .usuarios-list {
            display: inline-block;
            margin-left: 8px;
        }

        .usuario-badge {
            display: inline-block;
            background: #e7f3ff;
            color: #004085;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.8em;
            margin-right: 4px;
            margin-top: 2px;
        }

        .zonas-info {
            margin-left: 12px;
            font-size: 0.85em;
            color: #856404;
        }

        .zona-badge {
            display: inline-block;
            background: #fff3cd;
            color: #856404;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 0.75em;
            margin-right: 4px;
            margin-top: 2px;
        }

        .tree-children {
            margin-left: 30px;
            border-left: 2px dashed #dee2e6;
            padding-left: 15px;
            margin-top: 5px;
        }

        .icon-user {
            color: #007bff;
            margin-right: 4px;
        }

        .icon-zone {
            color: #ffc107;
            margin-right: 4px;
        }

        .tree-item-content .icon-user:before {
            content: "👤";
        }

        .tree-item-content .icon-zone:before {
            content: "📍";
        }

        .empty-message {
            color: #6c757d;
            font-style: italic;
            margin-left: 20px;
        }
    </style>
@endsection

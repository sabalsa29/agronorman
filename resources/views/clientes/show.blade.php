@extends('layouts.web')
@section('title', $section_name)
@section('ruta_home', route('clientes.index'))
@section('styles')
    <!-- Theme JS files -->
    <script src="{{ url('global_assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ url('assets/js/app.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/form_inputs.js') }}"></script>
    <!-- /theme JS files -->
@endsection
@section('content')
    <!-- Profile info -->
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

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-form-label col-lg-4 font-weight-semibold">Nombre:</label>
                        <div class="col-lg-8">
                            <span class="form-control-plaintext">{{ $cliente->nombre ?? 'No especificado' }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-form-label col-lg-4 font-weight-semibold">Empresa:</label>
                        <div class="col-lg-8">
                            <span class="form-control-plaintext">{{ $cliente->empresa ?? 'No especificada' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-form-label col-lg-4 font-weight-semibold">Teléfono:</label>
                        <div class="col-lg-8">
                            <span class="form-control-plaintext">{{ $cliente->telefono ?? 'No especificado' }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-form-label col-lg-4 font-weight-semibold">Ubicación:</label>
                        <div class="col-lg-8">
                            <span class="form-control-plaintext">{{ $cliente->ubicacion ?? 'No especificada' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-form-label col-lg-4 font-weight-semibold">Estatus:</label>
                        <div class="col-lg-8">
                            @if ($cliente->status == 1)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-danger">Inactivo</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-form-label col-lg-4 font-weight-semibold">Fecha de Creación:</label>
                        <div class="col-lg-8">
                            <span
                                class="form-control-plaintext">{{ $cliente->created_at ? $cliente->created_at->format('d/m/Y H:i:s') : 'No disponible' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-form-label col-lg-4 font-weight-semibold">Última Actualización:</label>
                        <div class="col-lg-8">
                            <span
                                class="form-control-plaintext">{{ $cliente->updated_at ? $cliente->updated_at->format('d/m/Y H:i:s') : 'No disponible' }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group row">
                        <label class="col-form-label col-lg-4 font-weight-semibold">Productores Asociados:</label>
                        <div class="col-lg-8">
                            <span class="form-control-plaintext">{{ $cliente->users->count() }} productores</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-right mt-4">
                <a href="{{ route('clientes.index') }}" class="btn btn-secondary mr-2">
                    <i class="icon-arrow-left8 mr-2"></i>Volver
                </a>
                <a href="{{ route('clientes.edit', $cliente) }}" class="btn btn-primary mr-2">
                    <i class="icon-pencil7 mr-2"></i>Editar
                </a>
                <a href="{{ route('usuarios.index', $cliente) }}" class="btn btn-warning mr-2">
                    <i class="icon-users mr-2"></i>Ver Productores
                </a>
                <a href="{{ route('parcelas.index', $cliente) }}" class="btn btn-success">
                    <i class="icon-menu6 mr-2"></i>Ver Parcelas
                </a>
            </div>
        </div>
    </div>
    <!-- /profile info -->
@endsection

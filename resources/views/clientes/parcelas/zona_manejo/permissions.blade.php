@extends('layouts.web')
@section('title', $section_name)
@section('modelo', 'Agregar')
@section('ruta_home', route('clientes.index'))
@section('ruta_alternativa', route('parcelas.index', ['id' => $cliente_id]))
@section('title_ruta_interna', 'Parcelas')
@section('ruta_create', route('zona_manejo.create', ['id' => $cliente_id, 'parcela_id' => $parcela_id]))
@section('styles')
    <!-- Theme JS files -->
    <script src="{{ url('global_assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/styling/switchery.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/forms/styling/switch.min.js') }}"></script>

    <script src="{{ url('assets/js/app.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/form_checkboxes_radios.js') }}"></script>
    <!-- /theme JS files -->
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

            <form action="{{ route('store_zona_manejos_user.store', ['id' => $cliente_id, 'parcela_id' => $parcela_id]) }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                <fieldset class="mb-3">

                    <div class="form-group row">
                        <div class="col-lg-12">
                            <div class="form-group mb-3 mb-md-2">
                                <div class="row">
                                    <div class="col-md-6">
                                        <input type="hidden" name="zona_manejo_id" value="{{ $zona_manejo->id }}">
                                        <input type="hidden" name="parcela_id" value="{{ $parcela_id }}">
                                        <input type="hidden" name="cliente_id" value="{{ $cliente_id }}">
                                        @foreach ($usuarios as $user)
                                            <div class="form-check">
                                                <label class="form-check-label">
                                                    <input type="checkbox" name="user_id[]" value="{{ $user->id }}"
                                                        {{ in_array($user->id, $selectedUserIds) ? 'checked' : '' }}
                                                        class="form-check-input-styled-primary" data-fouc>
                                                    {{ $user->nombre }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </fieldset>

                <div class="text-right">
                    <button type="submit" class="btn btn-primary">Agregar <i class="icon-paperplane ml-2"></i></button>
                </div>
            </form>
        </div>
    </div>
    <!-- /form inputs -->
@endsection

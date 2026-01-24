<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ config('app.name_title') }} - @yield('title', 'Formulario')</title>

    <!-- Global stylesheets -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
    <link href="{{ url('global_assets/css/icons/icomoon/styles.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ url('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ url('assets/css/bootstrap_limitless.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ url('assets/css/layout.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ url('assets/css/components.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ url('assets/css/colors.min.css') }}" rel="stylesheet" type="text/css">
    <!-- /global stylesheets -->

    <!-- Core JS files --> 
    <script src="{{ url('global_assets/js/main/jquery.min.js') }}"></script>
    <script src="{{ url('global_assets/js/main/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ url('global_assets/js/plugins/loaders/blockui.min.js') }}"></script>
    <!-- /core JS files -->

    @yield('meta')

    @section('styles')
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    @show

    <style>
        .swal2-icon-content {
            font-size: 5rem !important;
        }

        .swal2-icon.swal2-success [class^=swal2-success-line][class$=tip] {
            height: 0.3rem !important;
            width: 1.50rem !important;
            border-right: .25rem solid #66bb6a !important;
            border-top: .1rem solid #66bb6a !important;
            position: absolute;
            left: 1rem !important;
            top: 2.625rem !important;
        }

        .loader-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 10px;
        }

        .loader {
            border: 8px solid #f3f3f3;
            border-top: 8px solid #3498db;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .loader-text {
            color: #fff;
            font-size: 1.2rem;
            font-weight: bold;
        }
    </style>


</head>

<body class="sidebar-xs">

    @if (session('success'))
        <script>
            function showSuccessMessage() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Éxito!',
                        text: '{{ session('success') }}',
                        confirmButtonColor: '#3085d6'
                    });
                } else {
                    setTimeout(showSuccessMessage, 100);
                }
            }
            document.addEventListener('DOMContentLoaded', function() {
                showSuccessMessage();
            });
        </script>
    @endif

    @if ($errors->any())
        <script>
            function showErrorMessage() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Error',
                        icon: 'error',
                        html: '{!! implode('<br>', $errors->all()) !!}',
                        confirmButtonText: 'OK'
                    });
                } else {
                    setTimeout(showErrorMessage, 100);
                }
            }
            document.addEventListener('DOMContentLoaded', function() {
                showErrorMessage();
            });
        </script>
    @endif

    <!-- Main navbar -->
    @include('layouts.navbar')
    <!-- /main navbar -->


    <!-- Page content -->
    <div class="page-content">

        <!-- Main sidebar -->
        @include('layouts.sidebar')
        <!-- /main sidebar -->


        <!-- Main content -->
        <div class="content-wrapper">

            <!-- Page header -->
            <div class="page-header page-header-light">
                <div class="page-header-content header-elements-md-inline">
                    <div class="page-title d-flex">
                        <h4>@yield('title')</h4>
                        <a href="#" class="header-elements-toggle text-default d-md-none"><i
                                class="icon-more"></i></a>
                    </div>
                </div>

                <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                    <div class="d-flex">
                        <div class="breadcrumb">
                            <a href="@yield('ruta_home')" class="breadcrumb-item"><i class="icon-home2 mr-2"></i>
                                @yield('title')</a>
                            @if (View::hasSection('ruta_alternativa'))
                                <a href="@yield('ruta_alternativa')" class="breadcrumb-item"><i class="icon-home2 mr-2"></i>
                                    @yield('title_ruta_interna')</a>
                            @endif

                            <span class="breadcrumb-item active">Listado @yield('modelo')</span>
                        </div>

                        <a href="#" class="header-elements-toggle text-default d-md-none"><i
                                class="icon-more"></i></a>
                    </div>

                    <div class="header-elements d-none">
                        @if (View::hasSection('ruta_create'))
                            <div class="breadcrumb justify-content-center">
                                <a href="@yield('ruta_create')" class="breadcrumb-elements-item">
                                    <i class="icon-plus2 mr-2"></i>
                                    Agregar
                                </a>

                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <!-- /page header -->

            <!-- Content area -->
            <div class="content">
                @yield('content')
            </div>
            <!-- /content area -->


            <!-- Footer -->
            @include('layouts.footer')
            <!-- /footer -->

        </div>
        <!-- /main content -->

    </div>
    <!-- /page content -->
    @vite(['resources/js/app.js'])
    @yield('scripts')
    <script>
        window.addEventListener('load', function() {
            const loader = document.getElementById('loader');
            if (loader) {
                loader.style.display = 'none';
            }
        });

        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function() {
                const loader = document.getElementById('loader');
                if (loader) loader.style.display = 'flex';
            });
        });
    </script>
</body>

</html>

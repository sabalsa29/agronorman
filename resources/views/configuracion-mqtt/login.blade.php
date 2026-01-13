<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Configuración MQTT | Login</title>

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

    <!-- Theme JS files -->
    <script src="{{ url('global_assets/js/plugins/forms/styling/uniform.min.js') }}"></script>
    <script src="{{ url('assets/js/app.js') }}"></script>
    <script src="{{ url('global_assets/js/demo_pages/login.js') }}"></script>
    <!-- /theme JS files -->
    <style>
        .color_background {
            background-color: #4D4B44;
        }
    </style>
</head>

<body class="color_background">

    <!-- Page content -->
    <div class="page-content">

        <!-- Main content -->
        <div class="content-wrapper">

            <!-- Content area -->
            <div class="content d-flex justify-content-center align-items-center">

                <!-- Login card -->
                <form class="login-form" action="{{ route('configuracion-mqtt.login') }}" method="POST">
                    @csrf
                    <div class="card mb-0">
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <img src="{{ url('assets/images/logo.png') }}" style="width: 250px;" alt="">
                                <h4 class="mt-3">Configuración MQTT</h4>
                                <p class="text-muted">Acceso restringido</p>
                            </div>

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="form-group form-group-feedback form-group-feedback-left">
                                <input type="text" name="username" class="form-control" placeholder="Usuario" value="{{ old('username') }}" required>
                                <div class="form-control-feedback">
                                    <i class="icon-user text-muted"></i>
                                </div>
                            </div>

                            <div class="form-group form-group-feedback form-group-feedback-left">
                                <input type="password" name="password" class="form-control" placeholder="Contraseña" required>
                                <div class="form-control-feedback">
                                    <i class="icon-lock2 text-muted"></i>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-success btn-block">Iniciar sesión <i
                                        class="icon-circle-right2 ml-2"></i></button>
                            </div>

                            <span class="form-text text-center text-muted">©{{ date('Y') }} <a
                                    href="#">Norman</a></span>
                        </div>
                    </div>
                </form>
                <!-- /login card -->

            </div>
            <!-- /content area -->

        </div>
        <!-- /main content -->

    </div>
    <!-- /page content -->

</body>

</html>


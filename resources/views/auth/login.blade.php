<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Norman | Login</title>

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
                <form class="login-form" action="{{ route('login') }}" method="POST">
                    @csrf
                    <div class="card mb-0"> 
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <img src="{{ url('assets/images/logo.png') }}" style="width: 250px;" alt="">
                            </div>

                            <div class="form-group form-group-feedback form-group-feedback-left">
                                <input type="text" name="email" class="form-control" placeholder="Usuario">
                                <div class="form-control-feedback">
                                    <i class="icon-user text-muted"></i>
                                </div>
                            </div>

                            <div class="form-group form-group-feedback form-group-feedback-left">
                                <input type="password" name="password" class="form-control" placeholder="Contraseña">
                                <div class="form-control-feedback">
                                    <i class="icon-lock2 text-muted"></i>
                                </div>
                            </div>

                            <div class="form-group d-flex align-items-center">
                                <div class="form-check mb-0">
                                    <label class="form-check-label">
                                        <input type="checkbox" name="remember" class="form-input-styled" checked
                                            data-fouc>
                                        Recordarme
                                    </label>
                                </div>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="ml-auto">¿Olvidó su
                                        contraseña?</a>
                                @endif
                            </div>

                            @if (Route::has('register'))
                                <div class="form-group">
                                    <a href="{{ route('register') }}" class="btn btn-light btn-block">Registrarme</a>
                                </div>
                            @endif


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

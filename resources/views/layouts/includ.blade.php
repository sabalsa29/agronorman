<html>

<head>
    <!-- Global stylesheets -->
    <link href="{{ url('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <!-- /global stylesheets -->

    <script src="{{ url('global_assets/js/plugins/visualization/echarts/echarts.min.js') }}"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    @yield('content')

    @stack('scripts')
</body>

</html>

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8"  />
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'LTI Shim') }} - @yield('title')</title>

        <!-- Scripts -->
        <script src="{{ mix('js/midway.js') }}" defer></script>

        <!-- Styles -->
        <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    </head>
    <body>
        <div id='app' class='container'>
            @yield('content')
        </div>
    </body>
</html>

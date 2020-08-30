<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8"  />
        <title>{{ config('app.name', 'LTI Shim') }} - @yield('title')</title>
        <style>
            #autoSubmitForm {
                display: none;
            }
            p {
                text-align: center;
                font-size: 0.8em;
                color: lightgray;
            }
        </style>
    </head>
    <body>
        <div>
            @yield('content')
        </div>
        <script>
            window.addEventListener('load', function (event) {
                var autoSubmitForm = document.getElementById('autoSubmitForm');
                if (autoSubmitForm) autoSubmitForm.submit();
            });
        </script>
    </body>
</html>

<!DOCTYPE html>
<html style='height:100%' lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" type="image/png" href="{{env('APP_FAVICON', '/global/assets/images/branding/favicon.png')}}">
        <title>{{env('APP_NAME', 'Goa Solutions')}}</title>
        @include ('/styles/base')
    </head>
    <body style='margin:0px;display:flex;height:100%'>
        <script>
            window.GOA_APP_LOGO = "{{env('APP_LOGO')}}";
            window.GOA_APP_COLOR = "{{env('APP_COLOR')}}";
        </script>
        <div style='flex:1;display:flex;height:100%;max-width:100%;max-height:100%' id='goa'></div>
		<script src="/global/assets/js/views/authenticate.min.js"></script>
    </body>
</html>

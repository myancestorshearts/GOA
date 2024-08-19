<!DOCTYPE html>
<html style='height:100%' lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" type="image/png" href="{{env('APP_FAVICON', '/global/assets/images/branding/favicon.png')}}">
        <title>{{env('APP_NAME', 'Goa Solutions')}}</title>
		<link rel="stylesheet" type="text/css" href="/css/fonts/font-awesome/all.min.css">
        @include ('/styles/base')
    </head>
    <body style='margin:0px;display:flex;height:100%;width:100%;'>
        <script>
            window.GOA_ENVIRONMENT = "{{env('APP_ENV')}}";
            window.GOA_APP_LOGO = "{{env('APP_LOGO')}}";
            window.GOA_APP_COLOR = "{{env('APP_COLOR')}}";
            window.GOA_GATEWAY_CC_FEE = "{{env('GATEWAY_CC_FEE', .03)}}";
        </script>
        <div style='flex:1;display:flex;height:100%;max-width:100%;max-height:100%' id='goa'></div>
        <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDRpyjbMUq2HCuEsV_8Gf-E4yZdjrbrl-c&libraries=places"></script>
		<script src="/global/assets/js/views/portal.min.js"></script>
        <script>
            window.fwSettings={'widget_id':70000002981};
            !function(){if("function"!=typeof window.FreshworksWidget){var n=function(){n.q.push(arguments)};n.q=[],window.FreshworksWidget=n}}()
        </script>
        <script type='text/javascript' src='https://widget.freshworks.com/widgets/70000002981.js' async defer></script>    
    </body>
</html>
    
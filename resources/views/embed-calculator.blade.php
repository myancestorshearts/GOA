<!DOCTYPE html>
<html style='height:100%' lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" type="image/png" href="{{env('APP_FAVICON', '/global/assets/images/branding/favicon.png')}}">
		<link rel="stylesheet" type="text/css" href="/css/fonts/font-awesome/all.min.css">
        <title>Embed Calculator</title>
        @include ('/styles/base')
    </head>
    <body style='margin:200px;display:flex;height:100%;background-color:#03006d'>
        <div style='flex:1;display:flex;height:100%;max-width:100%;max-height:100%' id='shipping-calculator'></div>
		<script src="/global/assets/js/views/embed-calculator.min.js"></script>
    </body>
</html>
     
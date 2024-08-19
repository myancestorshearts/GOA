<html>
	<head>
	    <meta http-equiv="Content-Type" content="text/html;">
	</head>
	<body style="background: #f6f6f6;padding: 30px 0;">
		<div style="width: 600px;margin: auto;background: #FFF;border-radius: 10px;box-sizing: border-box;box-shadow: 0px 2px 10px #e7e7e7;overflow: hidden;">
			@include('emails/layout/header')
			@yield('page_content')
			@include('emails/layout/footer')
		</div>
	</body>
</html>
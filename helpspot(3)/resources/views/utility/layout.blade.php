<!DOCTYPE html>
<html lang="en" class="loginscreen">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width">
    <title>{{ $title }} @if( $orgname ){{ ': '.$orgname }}@endif</title>

	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="shortcut icon" href="/favicon.ico">
	<link rel="manifest" href="/site.webmanifest">

    @yield('scripts_styles_includes')
    @yield('theme_js')
</head>
<body>
    <div id="hs_msg" style="display:none;"></div>
    @yield('body')
</body>
</html>

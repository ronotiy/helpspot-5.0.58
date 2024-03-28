<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ url('apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ url('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ url('favicon-16x16.png') }}">
    <link rel="shortcut icon" href="{{ url('favicon.ico') }}">
    <link rel="manifest" href="{{ url('site.webmanifest') }}">

    <title>@yield('title')</title>

    <link href="{{ static_url().mix('static/css/public.css') }}" rel="stylesheet">
</head>
<body>
<div>
    @yield('content')
</div>
</body>
</html>

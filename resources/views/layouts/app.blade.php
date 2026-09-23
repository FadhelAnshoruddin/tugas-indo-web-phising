<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#102a43">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('icons/lucky-loop.svg') }}" type="image/svg+xml">
    <title>@yield('title', 'Lucky Loop')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <button id="pwaInstallButton" class="pwa-install-button" type="button" hidden>Install aplikasi</button>
    @yield('content')
</body>
</html>
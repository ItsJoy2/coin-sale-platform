<!DOCTYPE html>
<html lang="en">
  <head>
    @php
    $faviconLogo = \App\Models\Setting::where('key', 'favicon')->value('value');
    @endphp
    <meta charset="utf-8">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="author" content="MINDCHAIN ECOSYSTEM">
    <title>Mindchain Wallet - Admin Authentication </title>
    <link rel="apple-touch-icon" sizes="57x57" href="{{ $faviconLogo ? asset('storage/' . $faviconLogo) : asset('assets/mindchainwalletfab.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ $faviconLogo ? asset('storage/' . $faviconLogo) : asset('assets/mindchainwalletfab.png') }}">
    <link rel="manifest" href="{{ asset('assets/favicon/manifest.json') }}">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="{{ $faviconLogo ? asset('storage/' . $faviconLogo) : asset('assets/mindchainwalletfab.png') }}">
    <meta name="theme-color" content="#ffffff">
    <!-- Vendors styles-->
    @include('admin.layouts.auth-partials.__styles')
  </head>
  <body>
    @yield('auth-content')

    @include('admin.layouts.auth-partials.__scripts')
  </body>
</html>

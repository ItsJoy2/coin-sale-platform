<!DOCTYPE html>

<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="author" content="MINDCHAIN ECOSYSTEM">
    <title>MINDCHAIN - @yield('title')</title>
    <link rel="apple-touch-icon" sizes="57x57" href="{{ asset('assets/mindchainwalletfab.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('assets/mindchainwalletfab.png') }}">
    <link rel="manifest" href="assets/favicon/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="{{ asset('assets/mindchainwalletfab.png') }}">
    <meta name="theme-color" content="#ffffff">

    {{-- CSS Files --}}
    @include('admin.layouts.partials.__styles')

  </head>
  <body>
    {{-- Sidebar --}}
    @include('admin.layouts.partials.__sidebar')

    <div class="wrapper d-flex flex-column min-vh-100">

        {{-- Header --}}
        @include('admin.layouts.partials.__header')

      <div class="body flex-grow-1">

        {{-- Main content --}}
        @yield('content')

      </div>

      {{-- Footer section --}}
      @include('admin.layouts.partials.__footer')

    </div>

    {{-- Scripts section --}}
    @include('admin.layouts.partials.__scripts')
  </body>
</html>

<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CHIBU')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        :root { --safe-bottom: env(safe-area-inset-bottom, 0px); --safe-top: env(safe-area-inset-top, 0px); }
        body { -webkit-tap-highlight-color: transparent; overscroll-behavior-y: none; }
        .pb-nav { padding-bottom: calc(72px + var(--safe-bottom)); }
        .pt-safe { padding-top: var(--safe-top); }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-screen flex flex-col">
        @auth
            @include('partials.topbar')
        @endauth

        <main class="flex-1 @auth pb-nav @endauth">
            {{ $slot ?? '' }}
            @yield('content')
        </main>

        @auth
            @include('partials.bottom-nav')
        @endauth
    </div>

    @livewireScripts
</body>
</html>

<!DOCTYPE html>
<html lang="uz" class="">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CHIBU')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
    <style>
        :root { --safe-bottom: env(safe-area-inset-bottom, 0px); --safe-top: env(safe-area-inset-top, 0px); }
        body { -webkit-tap-highlight-color: transparent; overscroll-behavior-y: none; }
        .pb-nav { padding-bottom: calc(72px + var(--safe-bottom)); }
        .pt-safe { padding-top: var(--safe-top); }
        [x-cloak] { display: none !important; }
    </style>
</head>
<script>
    (function(){
        var t=localStorage.theme;
        if(t==='dark'||(t===undefined&&window.matchMedia('(prefers-color-scheme:dark)').matches)){
            document.documentElement.classList.add('dark');
        }
    })();
</script>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 antialiased transition-colors duration-200">
    <!-- Offline overlay (hidden by default, shown on JS offline event for non-dashboard pages) -->
    <div id="offline-overlay" style="display:none"
         class="fixed inset-0 bg-slate-900/90 z-[999] flex-col items-center justify-center gap-4 text-center px-8">
        <div class="text-6xl">📡</div>
        <div class="text-white text-2xl font-bold">Siz oflayn</div>
        <div class="text-slate-300 text-sm">Internet aloqasini tekshiring</div>
    </div>

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
    @stack('scripts')
    @auth
    <script>
        // Capacitor push notification registration (only in native app)
        (function() {
            if (!window.Capacitor || !window.Capacitor.isNativePlatform()) return;
            import('https://cdn.jsdelivr.net/npm/@capacitor/push-notifications@6/dist/esm/index.js')
                .then(({ PushNotifications }) => {
                    PushNotifications.requestPermissions().then(result => {
                        if (result.receive !== 'granted') return;
                        PushNotifications.register();
                    });
                    PushNotifications.addListener('registration', token => {
                        fetch('/api/device-token', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ token: token.value }),
                        });
                    });
                    PushNotifications.addListener('pushNotificationReceived', notification => {
                        // Refresh notification badge when push arrives in foreground
                        window.dispatchEvent(new Event('livewire:navigate'));
                    });
                    PushNotifications.addListener('pushNotificationActionPerformed', () => {
                        window.location.href = '/app/notifications';
                    });
                })
                .catch(() => {});
        })();
    </script>
    @endauth
    <script>
        (function () {
            const overlay = document.getElementById('offline-overlay');
            const isDashboard = () => {
                const p = window.location.pathname.replace(/\/$/, '');
                return p === '/app' || p === '';
            };
            function updateStatus() {
                if (overlay) overlay.style.display = (!navigator.onLine && !isDashboard()) ? 'flex' : 'none';
            }
            window.addEventListener('online',  updateStatus);
            window.addEventListener('offline', updateStatus);
            document.addEventListener('livewire:navigated', updateStatus);
            updateStatus();
        })();
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>APF POS - Cashier Terminal</title>
    
    <!-- PWA Meta -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#0f172a">
    <meta name="apple-mobile-web-app-capable" content="yes">
    
    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-900 overflow-hidden">

    <!-- Vue 3 App Mount Point -->
    <div id="app"
         data-branch-id="{{ auth()->user()->branch_id ?? App\Models\Branch::first()->id ?? 1 }}"
         data-cashier-id="{{ auth()->user()->id }}"
         data-cashier-name="{{ auth()->user()->name }}"
         data-branch-name="{{ App\Models\Branch::find(auth()->user()->branch_id ?? App\Models\Branch::first()->id ?? 1)->name ?? 'Main Branch' }}">
    </div>

    <!-- Register PWA Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('[PWA] Service Worker registered: ', reg.scope))
                    .catch(err => console.error('[PWA] Service Worker registration failed: ', err));
            });
        }
    </script>
</body>
</html>

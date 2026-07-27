{{-- Installable web app (home screen / desktop) --}}
<link rel="manifest" href="{{ route('pwa.manifest') }}">
<meta name="theme-color" content="#405189">
<meta name="mobile-web-app-capable" content="yes">
<meta name="application-name" content="{{ getSetting('app_name', config('app.name')) }}">

{{-- iOS home screen support: Safari ignores the manifest for these --}}
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="{{ getSetting('app_name', config('app.name')) }}">
<link rel="apple-touch-icon" href="{{ assetVersioned('assets/images/pwa/apple-touch-icon.png') }}">

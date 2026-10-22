{{-- Application installable (PWA) : manifeste, icones, couleur de la barre du telephone. --}}
<link rel="manifest" href="{{ route('manifest') }}">
<meta name="theme-color" content="#1f3864">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
<link rel="icon" type="image/png" href="/favicon.png">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">

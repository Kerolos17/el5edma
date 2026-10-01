<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#0f172a">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('join_requests.waiting_title') }} - {{ __('web_app.brand.name') }}</title>

    @php $cspNonce = request()->attributes->get('_csp_nonce', ''); @endphp

    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/web-app.css'])
    @livewireStyles
</head>

<body class="web-app-body">
    <style>
        .waiting-shell {
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: #f6f7fb;
        }

        [data-theme='dark'] .waiting-shell {
            background: #0f172a;
        }

        .waiting-card {
            width: 100%;
            max-width: 34rem;
            background: #fff;
            border: 1px solid #e7e8ef;
            border-radius: 1.25rem;
            padding: 2rem 1.5rem;
            box-shadow: 0 12px 32px rgb(15 23 42 / 8%);
            text-align: center;
            font-family: 'Cairo', sans-serif;
        }

        [data-theme='dark'] .waiting-card {
            background: #1e293b;
            border-color: #334155;
        }
    </style>

    <div class="waiting-shell">
        <div class="waiting-card">
            {{ $slot }}
        </div>
    </div>

    @livewireScripts
</body>

</html>

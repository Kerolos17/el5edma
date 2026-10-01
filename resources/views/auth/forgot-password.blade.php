<!DOCTYPE html>
<html dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('auth.reset.request_title') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Cairo', sans-serif;
            background: #f0f4f8;
            color: #1a2332;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .card {
            width: 100%;
            max-width: 26rem;
            background: #fff;
            border: 1px solid #e7e8ef;
            border-radius: 16px;
            padding: 2rem 1.5rem;
        }

        .card h1 { font-size: 1.15rem; margin-bottom: .4rem; color: #0f1e2d; }
        .card p.lead { font-size: .85rem; color: #64748b; margin-bottom: 1.5rem; line-height: 1.7; }

        .field { margin-bottom: 1.1rem; }
        .field label { display: block; font-size: .8rem; font-weight: 600; margin-bottom: .35rem; color: #374151; }
        .field input {
            width: 100%; height: 44px; padding: 0 .75rem; border-radius: 8px;
            border: 1.5px solid #d1d9e0; background: #fff; font-family: inherit; font-size: .9rem;
            outline: none;
        }
        .field input:focus { border-color: #0073A3; box-shadow: 0 0 0 3px rgba(0, 115, 163, .12); }
        .field small.error { display: block; color: #dc2626; font-size: .78rem; margin-top: .25rem; }

        .btn {
            width: 100%; height: 46px; border: none; border-radius: 8px;
            background: #0073A3; color: #fff; font-family: inherit; font-size: .92rem;
            font-weight: 600; cursor: pointer;
        }
        .btn:hover { background: #005880; }

        .status {
            background: #e6f7ee; border: 1px solid #b7e4c7; color: #0f7b55;
            border-radius: 8px; padding: .7rem .9rem; font-size: .84rem; margin-bottom: 1.1rem; line-height: 1.6;
        }

        .back-link {
            display: block; margin-top: 1.25rem; text-align: center;
            color: #0073A3; font-weight: 600; font-size: .85rem; text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="card">
        <h1>{{ __('auth.reset.request_title') }}</h1>
        <p class="lead">{{ __('auth.reset.request_lead') }}</p>

        @if (session('status'))
            <div class="status" role="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="field">
                <label for="email">{{ __('auth.reset.email_label') }}</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required dir="ltr"
                    autocomplete="email" placeholder="name@example.com">
                @error('email') <small class="error">{{ $message }}</small> @enderror
            </div>

            <button type="submit" class="btn">{{ __('auth.reset.send_link') }}</button>
        </form>

        <a class="back-link" href="{{ route('filament.admin.auth.login') }}">{{ __('auth.reset.back_to_login') }}</a>
    </div>
</body>

</html>

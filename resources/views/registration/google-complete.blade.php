<!DOCTYPE html>
<html dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('registration.google_title') }}</title>
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
            max-width: 30rem;
            background: #fff;
            border: 1px solid #e7e8ef;
            border-radius: 16px;
            padding: 2rem 1.5rem;
        }

        .card h1 { font-size: 1.2rem; margin-bottom: .4rem; color: #0f1e2d; }
        .card p.lead { font-size: .85rem; color: #64748b; margin-bottom: 1.5rem; }

        .field { margin-bottom: 1rem; }
        .field label { display: block; font-size: .8rem; font-weight: 600; margin-bottom: .35rem; color: #374151; }
        .field input, .field select {
            width: 100%; height: 44px; padding: 0 .75rem; border-radius: 8px;
            border: 1.5px solid #d1d9e0; background: #fff; font-family: inherit; font-size: .9rem;
            outline: none;
        }
        .field input:focus, .field select:focus { border-color: #0073A3; }
        .field input[readonly] { background: #f1f5f9; color: #475569; }
        .field small.error { display: block; color: #dc2626; font-size: .78rem; margin-top: .25rem; }
        .hint { font-size: .75rem; color: #64748b; margin-top: .25rem; }
        .consent { display: flex; gap: .6rem; align-items: flex-start; font-size: .82rem; margin: 1.25rem 0; }
        .consent input { width: 18px; height: 18px; margin-top: 2px; accent-color: #0073A3; }
        .consent a { color: #0073A3; font-weight: 600; }
        .btn {
            width: 100%; height: 46px; border: none; border-radius: 8px;
            background: #0073A3; color: #fff; font-family: inherit; font-size: .92rem;
            font-weight: 600; cursor: pointer;
        }
        .btn:hover { background: #005880; }
    </style>
</head>

<body>
    <div class="card">
        <h1>{{ __('registration.google_title') }}</h1>
        <p class="lead">{{ __('registration.google_lead') }}</p>

        @if (session('error'))
            <p class="field"><small class="error">{{ session('error') }}</small></p>
        @endif

        <form method="POST" action="{{ route('registration.google.complete', ['token' => $token]) }}">
            @csrf

            <div class="field">
                <label for="g-name">{{ __('registration.name') }}</label>
                <input id="g-name" type="text" value="{{ $googleName }}" readonly>
            </div>

            <div class="field">
                <label for="g-email">{{ __('registration.email') }}</label>
                <input id="g-email" type="email" value="{{ $googleEmail }}" readonly dir="ltr">
            </div>

            <div class="field">
                <label for="phone">{{ __('registration.phone') }}<span style="color:#0073A3">*</span></label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required dir="ltr"
                    autocomplete="tel" inputmode="tel" placeholder="01234567890">
                @error('phone') <small class="error">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="service_group_id">{{ __('registration.service_group') }}<span style="color:#0073A3">*</span></label>
                <select id="service_group_id" name="service_group_id" required>
                    <option value="">{{ __('registration.select_service_group') }}</option>
                    @foreach ($serviceGroups as $group)
                        <option value="{{ $group->id }}" {{ old('service_group_id') == $group->id ? 'selected' : '' }}>
                            {{ $group->name }}
                        </option>
                    @endforeach
                </select>
                @error('service_group_id') <small class="error">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="desired_role">{{ __('registration.desired_role') }}<span style="color:#0073A3">*</span></label>
                <select id="desired_role" name="desired_role" required>
                    <option value="servant" {{ old('desired_role', 'servant') === 'servant' ? 'selected' : '' }}>
                        {{ __('users.roles.servant') }}</option>
                    <option value="family_leader" {{ old('desired_role') === 'family_leader' ? 'selected' : '' }}>
                        {{ __('users.roles.family_leader') }}</option>
                    <option value="service_leader" {{ old('desired_role') === 'service_leader' ? 'selected' : '' }}>
                        {{ __('users.roles.service_leader') }}</option>
                </select>
                <p class="hint">{{ __('registration.role_hint') }}</p>
                @error('desired_role') <small class="error">{{ $message }}</small> @enderror
            </div>

            <div class="field">
                <label for="password">{{ __('registration.password') }}<span style="color:#0073A3">*</span></label>
                <input id="password" type="password" name="password" required minlength="8" dir="ltr"
                    autocomplete="new-password" placeholder="{{ __('registration.password_placeholder') }}">
                <p class="hint">{{ __('registration.google_password_hint') }}</p>
                @error('password') <small class="error">{{ $message }}</small> @enderror
            </div>

            <div class="consent">
                <input type="checkbox" id="privacy_consent" name="privacy_consent" value="1" required
                    @checked(old('privacy_consent'))>
                <label for="privacy_consent">
                    {{ __('registration.privacy_consent') }}
                    <a href="{{ route('privacy.show') }}" target="_blank" rel="noopener">
                        {{ __('registration.privacy_link') }}
                    </a>
                </label>
            </div>
            @error('privacy_consent') <small class="error">{{ $message }}</small> @enderror

            <button type="submit" class="btn">{{ __('registration.submit') }}</button>
        </form>
    </div>
</body>

</html>

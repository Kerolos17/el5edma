<!DOCTYPE html>
<html dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('registration.title') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap"
        rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --brand: #0073A3;
            --brand-dark: #005880;
            --brand-light: #e0f2fc;
            --panel-bg: #03243A;
            --panel-mid: #053a57;
            --success: #0f7b55;
            --danger: #c0392b;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 20px;
            --ease-out: cubic-bezier(0.22, 1, 0.36, 1);
        }

        html,
        body {
            min-height: 100%;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #f0f4f8;
            color: #1a2332;
        }

        /* ─── Layout ──────────────────────────────────────────── */
        .page-wrapper {
            display: flex;
            min-height: 100vh;
            align-items: stretch;
        }

        /* ─── Brand Panel ─────────────────────────────────────── */
        .brand-panel {
            display: none;
            flex-direction: column;
            justify-content: space-between;
            padding: 3rem 2.5rem;
            background: var(--panel-bg);
            position: relative;
            overflow: hidden;
            flex: 0 0 400px;
        }

        @media (min-width: 900px) {
            .brand-panel {
                display: flex;
            }
        }

        /* geometric decoration */
        .brand-panel::before {
            content: '';
            position: absolute;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            border: 1.5px solid rgba(0, 115, 163, .22);
            top: -120px;
            right: -130px;
        }

        .brand-panel::after {
            content: '';
            position: absolute;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            border: 1.5px solid rgba(0, 115, 163, .15);
            bottom: 60px;
            left: -80px;
        }

        .brand-dot-grid {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(0, 115, 163, .18) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        .brand-logo {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .brand-logo-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--brand);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-logo-icon svg {
            width: 22px;
            height: 22px;
        }

        .brand-logo-name {
            font-size: 1.05rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: -.3px;
        }

        .brand-body {
            position: relative;
            z-index: 1;
        }

        .brand-body h2 {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.3;
            color: #fff;
            margin-bottom: .75rem;
            letter-spacing: -.5px;
        }

        .brand-body p {
            font-size: .9rem;
            color: rgba(255, 255, 255, .55);
            line-height: 1.65;
        }

        .brand-features {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }

        .brand-feature {
            display: flex;
            align-items: center;
            gap: .75rem;
            font-size: .82rem;
            color: rgba(255, 255, 255, .6);
        }

        .brand-feature-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--brand);
            flex-shrink: 0;
        }

        /* ─── Form Panel ──────────────────────────────────────── */
        .form-panel {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
        }

        .form-card {
            width: 100%;
            max-width: 460px;
            animation: slideUp .5s var(--ease-out) both;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(18px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ─── Header ──────────────────────────────────────────── */
        .form-header {
            margin-bottom: 2rem;
        }

        .form-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f1e2d;
            letter-spacing: -.5px;
            margin-bottom: .3rem;
        }

        .form-header p {
            font-size: .875rem;
            color: #64748b;
        }

        /* ─── Alerts ──────────────────────────────────────────── */
        .alert {
            display: flex;
            gap: .75rem;
            align-items: flex-start;
            padding: .875rem 1rem;
            border-radius: var(--radius-md);
            font-size: .84rem;
            margin-bottom: 1.5rem;
            animation: slideUp .4s var(--ease-out) both;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        .alert svg {
            flex-shrink: 0;
            margin-top: 1px;
        }

        /* ─── Floating Label Field ────────────────────────────── */
        .field {
            position: relative;
            margin-bottom: 1.1rem;
        }

        .field label {
            display: block;
            font-size: .8rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: .4rem;
            letter-spacing: .01em;
        }

        .field label .req {
            color: var(--brand);
            margin-inline-start: 2px;
        }

        .field-input {
            width: 100%;
            height: 46px;
            padding: 0 .875rem;
            border-radius: var(--radius-sm);
            border: 1.5px solid #d1d9e0;
            background: #fff;
            font-family: inherit;
            font-size: .9rem;
            color: #1a2332;
            outline: none;
            transition: border-color .18s, box-shadow .18s;
        }

        select.field-input {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right .75rem center;
            padding-inline-end: 2.5rem;
        }

        [dir=rtl] select.field-input {
            background-position: left .75rem center;
            padding-inline-end: .875rem;
            padding-inline-start: 2.5rem;
        }

        .field-input:hover {
            border-color: #a5b4c3;
        }

        .field-input:focus {
            border-color: var(--brand);
            box-shadow: 0 0 0 3.5px rgba(0, 115, 163, .12);
        }

        .field-input.is-error {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, .1);
        }

        .field-error {
            font-size: .78rem;
            color: #dc2626;
            margin-top: .3rem;
            display: flex;
            align-items: center;
            gap: .3rem;
        }

        /* password wrapper */
        .password-wrap {
            position: relative;
        }

        .password-wrap .field-input {
            padding-inline-end: 2.75rem;
        }

        .pw-toggle {
            position: absolute;
            inset-inline-end: .75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 4px;
            color: #94a3b8;
            line-height: 0;
            transition: color .15s;
        }

        .pw-toggle:hover {
            color: var(--brand);
        }

        .pw-toggle svg {
            width: 18px;
            height: 18px;
        }

        /* password strength */
        .pw-strength {
            margin-top: .5rem;
        }

        .pw-strength-bar {
            height: 3px;
            border-radius: 99px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .pw-strength-fill {
            height: 100%;
            border-radius: 99px;
            width: 0%;
            transition: width .3s var(--ease-out), background .3s;
        }

        .pw-strength-label {
            font-size: .72rem;
            margin-top: .3rem;
            color: #94a3b8;
        }

        /* ─── Submit Button ───────────────────────────────────── */
        .btn-submit {
            width: 100%;
            height: 48px;
            margin-top: .5rem;
            border: none;
            border-radius: var(--radius-sm);
            background: var(--brand);
            color: #fff;
            font-family: inherit;
            font-size: .92rem;
            font-weight: 600;
            letter-spacing: .01em;
            cursor: pointer;
            transition: background .18s, transform .1s, box-shadow .18s;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0);
            transition: background .18s;
        }

        .btn-submit:hover {
            background: var(--brand-dark);
            box-shadow: 0 4px 18px rgba(0, 115, 163, .3);
        }

        .btn-submit:active {
            transform: scale(.98);
        }

        /* ─── Footer Link ─────────────────────────────────────── */
        .form-footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: .84rem;
            color: #64748b;
        }

        .form-footer a {
            color: var(--brand);
            font-weight: 600;
            text-decoration: none;
            transition: color .15s;
        }

        .form-footer a:hover {
            color: var(--brand-dark);
            text-decoration: underline;
        }

        /* ─── Success State ───────────────────────────────────── */
        .success-card {
            text-align: center;
            animation: slideUp .5s var(--ease-out) both;
        }

        .success-icon-wrap {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 115, 163, .09);
            position: relative;
        }

        .success-icon-wrap svg {
            width: 30px;
            height: 30px;
            color: var(--brand);
        }

        .success-ring {
            position: absolute;
            inset: -6px;
            border-radius: 50%;
            border: 1.5px solid rgba(0, 115, 163, .2);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.08);
                opacity: .5;
            }
        }

        .success-card h2 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #0f1e2d;
            margin-bottom: .5rem;
        }

        .success-card p {
            font-size: .88rem;
            color: #64748b;
            line-height: 1.65;
            margin-bottom: 1.5rem;
        }

        .success-note {
            background: rgba(0, 115, 163, .06);
            border: 1px solid rgba(0, 115, 163, .15);
            border-radius: var(--radius-md);
            padding: .875rem 1rem;
            font-size: .84rem;
            color: #374151;
            text-align: start;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: .625rem;
        }

        .success-note-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--brand);
            flex-shrink: 0;
        }

        /* ─── Dark Mode ───────────────────────────────────────── */
        @media (prefers-color-scheme: dark) {

            html,
            body {
                background: #0c1824;
                color: #e2e8f0;
            }

            .form-panel {
                background: transparent;
            }

            .form-header h1 {
                color: #f1f5f9;
            }

            .form-header p {
                color: #64748b;
            }

            .field label {
                color: #94a3b8;
            }

            .field-input {
                background: #111f2e;
                border-color: #1e3448;
                color: #e2e8f0;
            }

            .field-input:hover {
                border-color: #2e4a63;
            }

            .field-input:focus {
                border-color: var(--brand);
                box-shadow: 0 0 0 3.5px rgba(0, 115, 163, .18);
            }

            select.field-input option {
                background: #111f2e;
            }

            .alert-error {
                background: #2d0a0a;
                border-color: #7f1d1d;
                color: #fca5a5;
            }

            .success-card h2 {
                color: #f1f5f9;
            }

            .success-note {
                background: rgba(0, 115, 163, .1);
                border-color: rgba(0, 115, 163, .25);
                color: #cbd5e1;
            }

            .form-footer {
                color: #475569;
            }
        }

        /* ─── Staggered field animation ───────────────────────── */
        .field:nth-child(1) {
            animation: slideUp .45s .05s var(--ease-out) both;
        }

        .field:nth-child(2) {
            animation: slideUp .45s .10s var(--ease-out) both;
        }

        .field:nth-child(3) {
            animation: slideUp .45s .15s var(--ease-out) both;
        }

        .field:nth-child(4) {
            animation: slideUp .45s .20s var(--ease-out) both;
        }

        .field:nth-child(5) {
            animation: slideUp .45s .25s var(--ease-out) both;
        }

        .field:nth-child(6) {
            animation: slideUp .45s .30s var(--ease-out) both;
        }

        .btn-submit {
            animation: slideUp .45s .35s var(--ease-out) both;
        }

        /* ─── Success Modal ───────────────────────────────────── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
            background: rgba(3, 36, 58, 0.82);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            animation: modalFadeIn .3s var(--ease-out) both;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        .modal-card {
            background: #fff;
            border-radius: var(--radius-lg);
            overflow: hidden;
            max-width: 460px;
            width: 100%;
            box-shadow: 0 32px 100px rgba(0,0,0,.42), 0 0 0 1px rgba(255,255,255,.06);
            animation: modalSlideUp .5s var(--ease-out) both;
        }

        @keyframes modalSlideUp {
            from { opacity: 0; transform: translateY(28px) scale(.94); }
            to   { opacity: 1; transform: translateY(0)    scale(1);   }
        }

        .modal-hero {
            background: linear-gradient(145deg, var(--panel-bg) 0%, #054a6b 100%);
            padding: 2.75rem 2rem 3.75rem;
            position: relative;
            text-align: center;
        }

        .modal-icon-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .modal-icon-pulse {
            position: absolute;
            inset: -10px;
            border-radius: 50%;
            border: 2px solid rgba(0, 180, 216, .5);
            animation: pulse 2.2s ease-in-out infinite;
        }

        .modal-icon-pulse-2 {
            position: absolute;
            inset: -22px;
            border-radius: 50%;
            border: 1.5px solid rgba(0, 180, 216, .18);
            animation: pulse 2.2s .4s ease-in-out infinite;
        }

        .modal-icon-circle {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0073A3 0%, #00b4d8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 12px 36px rgba(0, 115, 163, .55);
        }

        .modal-icon-circle svg {
            width: 36px;
            height: 36px;
            color: #fff;
        }

        .modal-wave {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            overflow: hidden;
            line-height: 0;
        }

        .modal-wave svg {
            display: block;
            width: 100%;
            height: 40px;
        }

        .modal-body {
            padding: 1.875rem 2rem 2.25rem;
            text-align: center;
        }

        .modal-body h2 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #0f1e30;
            margin-bottom: .4375rem;
            line-height: 1.3;
        }

        .modal-tagline {
            font-size: .9375rem;
            color: #4b6078;
            line-height: 1.7;
            margin-bottom: 1.25rem;
        }

        .modal-badge {
            display: inline-flex;
            align-items: center;
            gap: .4375rem;
            background: #fffbeb;
            border: 1.5px solid #fde68a;
            border-radius: 100px;
            padding: .375rem 1rem;
            font-size: .8rem;
            font-weight: 600;
            color: #92400e;
            margin-bottom: 1.25rem;
        }

        .modal-badge svg {
            width: 13px;
            height: 13px;
            flex-shrink: 0;
        }

        .modal-note {
            display: flex;
            align-items: flex-start;
            gap: .625rem;
            background: var(--brand-light);
            border-inline-start: 3px solid var(--brand);
            border-radius: var(--radius-sm);
            padding: .875rem 1rem;
            text-align: start;
            font-size: .875rem;
            color: #1a4d6e;
            line-height: 1.65;
            margin-bottom: 1.75rem;
        }

        .modal-note svg {
            width: 16px;
            height: 16px;
            color: var(--brand);
            flex-shrink: 0;
            margin-top: 2px;
        }

        .modal-cta {
            display: block;
            text-align: center;
            text-decoration: none;
            width: 100%;
            padding: 0 1.5rem;
            height: 50px;
            line-height: 50px;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            color: #fff;
            font-family: inherit;
            font-size: .9375rem;
            font-weight: 600;
            letter-spacing: .01em;
            transition: opacity .2s, transform .15s;
            box-shadow: 0 4px 18px rgba(0, 115, 163, .38);
        }

        .modal-cta:hover {
            opacity: .9;
            transform: translateY(-1px);
        }

        .modal-cta:active {
            transform: scale(.98);
        }
    </style>
</head>

<body>
    <div class="page-wrapper">

        {{-- ─── Brand Panel ─── --}}
        <aside class="brand-panel">
            <div class="brand-dot-grid"></div>

            <div class="brand-logo">
                <div class="brand-logo-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                        stroke="#fff">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </div>
                <span class="brand-logo-name">{{ __('registration.panel_headline') }}</span>
            </div>

            <div class="brand-body">
                <h2>{{ __('registration.panel_headline') }}</h2>
                <p>{{ __('registration.panel_sub') }}</p>
            </div>

            <div class="brand-features">
                <div class="brand-feature">
                    <div class="brand-feature-dot"></div>
                    {{ __('registration.feature_1') }}
                </div>
                <div class="brand-feature">
                    <div class="brand-feature-dot"></div>
                    {{ __('registration.feature_2') }}
                </div>
                <div class="brand-feature">
                    <div class="brand-feature-dot"></div>
                    {{ __('registration.feature_3') }}
                </div>
            </div>
        </aside>

        {{-- ─── Form Panel ─── --}}
        <main class="form-panel">
            <div class="form-card">

                {{-- ─── Form Header ─── --}}
                <div class="form-header">
                    <h1>{{ __('registration.title') }}</h1>
                    <p>{{ __('registration.welcome') }}</p>
                </div>

                {{-- ─── Error Alert ─── --}}
                @if (session('error'))
                        <div class="alert alert-error" role="alert">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    {{-- ─── Registration Form ─── --}}
                    <form method="POST" action="{{ route('register.public.store') }}" novalidate>
                        @csrf

                        {{-- Name --}}
                        <div class="field">
                            <label for="name">{{ __('registration.name') }}<span class="req">*</span></label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required
                                autocomplete="name" class="field-input @error('name') is-error @enderror"
                                placeholder="{{ __('registration.name_placeholder') }}">
                            @error('name')
                                <p class="field-error">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="field">
                            <label for="email">{{ __('registration.email') }}<span class="req">*</span></label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required
                                autocomplete="email" class="field-input @error('email') is-error @enderror"
                                placeholder="{{ __('registration.email_placeholder') }}">
                            @error('email')
                                <p class="field-error">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Phone --}}
                        <div class="field">
                            <label for="phone">{{ __('registration.phone') }}<span class="req">*</span></label>
                            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}"
                                required autocomplete="tel" class="field-input @error('phone') is-error @enderror"
                                placeholder="{{ __('registration.phone_placeholder') }}">
                            @error('phone')
                                <p class="field-error">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Service Group --}}
                        <div class="field">
                            <label for="service_group_id">{{ __('registration.service_group') }}<span
                                    class="req">*</span></label>
                            <select id="service_group_id" name="service_group_id" required
                                class="field-input @error('service_group_id') is-error @enderror">
                                <option value="">{{ __('registration.select_service_group') }}</option>
                                @foreach ($serviceGroups as $group)
                                    <option value="{{ $group->id }}"
                                        {{ old('service_group_id') == $group->id ? 'selected' : '' }}>
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('service_group_id')
                                <p class="field-error">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div class="field">
                            <label for="password">{{ __('registration.password') }}<span
                                    class="req">*</span></label>
                            <div class="password-wrap">
                                <input type="password" id="password" name="password" required minlength="8"
                                    autocomplete="new-password"
                                    class="field-input @error('password') is-error @enderror"
                                    placeholder="{{ __('registration.password_placeholder') }}"
                                    oninput="checkStrength(this.value)">
                                <button type="button" class="pw-toggle" aria-label="Toggle password visibility"
                                    onclick="togglePw('password', this)">
                                    <svg id="eye-pw" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                </button>
                            </div>
                            <div class="pw-strength" id="pw-strength" style="display:none;">
                                <div class="pw-strength-bar">
                                    <div class="pw-strength-fill" id="pw-fill"></div>
                                </div>
                                <div class="pw-strength-label" id="pw-label"></div>
                            </div>
                            @error('password')
                                <p class="field-error">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" />
                                    </svg>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        {{-- Confirm Password --}}
                        <div class="field">
                            <label for="password_confirmation">{{ __('registration.password_confirmation') }}<span
                                    class="req">*</span></label>
                            <div class="password-wrap">
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                    required minlength="8" autocomplete="new-password" class="field-input"
                                    placeholder="{{ __('registration.password_confirmation_placeholder') }}">
                                <button type="button" class="pw-toggle"
                                    aria-label="Toggle confirm password visibility"
                                    onclick="togglePw('password_confirmation', this)">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit">
                            {{ __('registration.submit') }}
                        </button>
                    </form>

                    <p class="form-footer">
                        <a
                            href="{{ route('filament.admin.auth.login') }}">{{ __('registration.already_have_account') }}</a>
                    </p>

            </div>
        </main>

    </div>

    {{-- ─── Success Modal ─── --}}
    @if (session('success'))
        <div id="success-modal" class="modal-overlay" role="dialog" aria-modal="true"
             aria-labelledby="modal-heading">

            <div class="modal-card">

                {{-- Hero ─ dark branded header --}}
                <div class="modal-hero">
                    <div class="modal-icon-wrap">
                        <div class="modal-icon-pulse-2"></div>
                        <div class="modal-icon-pulse"></div>
                        <div class="modal-icon-circle">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                 stroke-width="2.2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </div>
                    </div>

                    {{-- Wave divider --}}
                    <div class="modal-wave">
                        <svg viewBox="0 0 460 40" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M0 40 Q115 0 230 20 Q345 40 460 0 L460 40 Z" fill="#ffffff"/>
                        </svg>
                    </div>
                </div>

                {{-- Body --}}
                <div class="modal-body">
                    <h2 id="modal-heading">{{ __('registration.success_title') }}</h2>
                    <p class="modal-tagline">{{ __('registration.success_message') }}</p>

                    {{-- Pending badge --}}
                    <div class="modal-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                             stroke-width="2" stroke="#d97706">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" />
                        </svg>
                        {{ __('registration.popup_awaiting') }}
                    </div>

                    {{-- Leader note --}}
                    <div class="modal-note">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                             stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                        </svg>
                        <span>{{ __('registration.popup_await_note') }}</span>
                    </div>

                    <a href="{{ route('filament.admin.auth.login') }}" class="modal-cta">
                        {{ __('registration.back_to_login') }}
                    </a>
                </div>

            </div>
        </div>
    @endif

    <script>
        function togglePw(id, btn) {
            var input = document.getElementById(id);
            var isText = input.type === 'text';
            input.type = isText ? 'password' : 'text';
            var eyePath = isText ?
                'M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z' :
                'M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88';
            btn.querySelector('svg').innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="' + eyePath +
                '"/>';
        }

        function checkStrength(val) {
            var bar = document.getElementById('pw-strength');
            var fill = document.getElementById('pw-fill');
            var label = document.getElementById('pw-label');
            if (!val) {
                bar.style.display = 'none';
                return;
            }
            bar.style.display = 'block';
            var score = 0;
            if (val.length >= 8) score++;
            if (val.length >= 12) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;
            var levels = [{
                    pct: '20%',
                    color: '#ef4444',
                    text: '{{ __('registration.pw_weak') }}'
                },
                {
                    pct: '40%',
                    color: '#f97316',
                    text: '{{ __('registration.pw_fair') }}'
                },
                {
                    pct: '60%',
                    color: '#eab308',
                    text: '{{ __('registration.pw_good') }}'
                },
                {
                    pct: '80%',
                    color: '#22c55e',
                    text: '{{ __('registration.pw_strong') }}'
                },
                {
                    pct: '100%',
                    color: '#0073A3',
                    text: '{{ __('registration.pw_great') }}'
                },
            ];
            var lvl = levels[Math.min(score - 1, 4)] || levels[0];
            fill.style.width = lvl.pct;
            fill.style.background = lvl.color;
            label.textContent = lvl.text;
            label.style.color = lvl.color;
        }
    </script>
</body>

</html>

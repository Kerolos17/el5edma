<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('registration.privacy_title') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Cairo', sans-serif;
            background: #f0f4f8;
            color: #1a2332;
            line-height: 1.9;
            padding: 2rem 1rem;
        }

        .policy-card {
            max-width: 46rem;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #e7e8ef;
            border-radius: 16px;
            padding: 2.5rem 2rem;
        }

        .policy-card h1 {
            font-size: 1.4rem;
            margin-bottom: 1.25rem;
            color: #0f1e2d;
        }

        .policy-card h2 {
            font-size: 1.05rem;
            margin: 1.5rem 0 .5rem;
            color: #0073A3;
        }

        .policy-card p, .policy-card li {
            font-size: .92rem;
            color: #374151;
        }

        .policy-card ul { padding-inline-start: 1.25rem; }

        .back-link {
            display: inline-block;
            margin-top: 2rem;
            color: #0073A3;
            font-weight: 600;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <article class="policy-card">
        <h1>{{ __('registration.privacy_title') }}</h1>

        <h2>{{ __('registration.privacy_what_title') }}</h2>
        <p>{{ __('registration.privacy_what_body') }}</p>
        <ul>
            <li>{{ __('registration.privacy_item_name') }}</li>
            <li>{{ __('registration.privacy_item_email') }}</li>
            <li>{{ __('registration.privacy_item_phone') }}</li>
            <li>{{ __('registration.privacy_item_group') }}</li>
        </ul>

        <h2>{{ __('registration.privacy_use_title') }}</h2>
        <p>{{ __('registration.privacy_use_body') }}</p>

        <h2>{{ __('registration.privacy_protection_title') }}</h2>
        <p>{{ __('registration.privacy_protection_body') }}</p>

        <h2>{{ __('registration.privacy_rights_title') }}</h2>
        <p>{{ __('registration.privacy_rights_body') }}</p>

        <a class="back-link" href="javascript:history.back()">{{ __('registration.privacy_back') }}</a>
    </article>
</body>

</html>

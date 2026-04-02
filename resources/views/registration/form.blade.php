<!DOCTYPE html>
<html dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('registration.title') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8">
        <h1 class="text-2xl font-bold text-center mb-6 text-gray-800">
            {{ __('registration.title') }}
        </h1>

        {{-- Service Group Info --}}
        <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-100">
            <p class="text-sm text-gray-700">
                <span class="font-medium">{{ __('registration.service_group') }}:</span>
                <strong class="text-blue-700">{{ $serviceGroup->name }}</strong>
            </p>
        </div>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-sm text-green-800">{{ session('success') }}</p>
            </div>
        @endif

        {{-- Error Message --}}
        @if (session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-sm text-red-800">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Registration Form --}}
        <form method="POST" action="{{ route('register.store', $token) }}" class="space-y-4">
            @csrf

            {{-- Name Field --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('registration.name') }} <span class="text-red-500">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                    placeholder="{{ __('registration.name_placeholder') }}">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Email Field --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('registration.email') }} <span class="text-red-500">*</span>
                </label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror"
                    placeholder="{{ __('registration.email_placeholder') }}">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Phone Field --}}
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('registration.phone') }} <span class="text-red-500">*</span>
                </label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('phone') border-red-500 @enderror"
                    placeholder="{{ __('registration.phone_placeholder') }}">
                @error('phone')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password Field --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('registration.password') }} <span class="text-red-500">*</span>
                </label>
                <input type="password" id="password" name="password" required minlength="8"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-500 @enderror"
                    placeholder="{{ __('registration.password_placeholder') }}">
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password Confirmation Field --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
                    {{ __('registration.password_confirmation') }} <span class="text-red-500">*</span>
                </label>
                <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    placeholder="{{ __('registration.password_confirmation_placeholder') }}">
            </div>

            {{-- Submit Button --}}
            <button type="submit"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 transition-colors font-medium">
                {{ __('registration.submit') }}
            </button>
        </form>

        {{-- Login Link --}}
        <div class="mt-6 text-center">
            <a href="{{ route('filament.admin.auth.login') }}" class="text-sm text-blue-600 hover:text-blue-800">
                {{ __('registration.already_have_account') }}
            </a>
        </div>
    </div>
</body>

</html>

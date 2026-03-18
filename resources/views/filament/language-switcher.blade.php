<form method="POST" action="{{ route('language.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}"
    class="flex items-center mx-5">
    @csrf
    <button type="submit"
        class="flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium rounded-full border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-100 hover:scale-105 transition-transform duration-150">
        @if (app()->getLocale() === 'ar')
            {{ __('auth.switch_to_english') }}
        @else
            {{ __('auth.switch_to_arabic') }}
        @endif
    </button>
</form>

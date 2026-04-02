<form method="POST" action="{{ route('language.switch', app()->getLocale() === 'ar' ? 'en' : 'ar') }}"
    class="flex items-center mx-2 sm:mx-5">
    @csrf
    <button type="submit"
        class="flex items-center justify-center gap-2 px-3 py-2 sm:px-4 sm:py-2 text-sm font-medium rounded-full border border-gray-300 bg-white text-gray-700 shadow-sm hover:bg-gray-100 hover:border-gray-400 hover:scale-105 transition duration-150 min-h-[44px]">
        @if (app()->getLocale() === 'ar')
            {{ __('auth.switch_to_english') }}
        @else
            {{ __('auth.switch_to_arabic') }}
        @endif
    </button>
</form>

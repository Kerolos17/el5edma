import { defineConfig } from 'vite'
import laravel, { refreshPaths } from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament/admin/theme.css',
                'resources/css/servant.css',
                'resources/js/servant.js',
                'resources/css/web-app.css',
                'resources/js/web-app.js',
            ],
            refresh: [
                ...refreshPaths,
                'app/Filament/**',
                'app/Providers/Filament/**',
                'resources/views/filament/**',
                'app/Livewire/Servant/**',
                'resources/views/servant/**',
                'resources/views/livewire/servant/**',
                'resources/views/components/servant/**',
                'app/Livewire/WebApp/**',
                'resources/views/web-app/**',
                'resources/views/livewire/web-app/**',
                'resources/views/components/web-app/**',
            ],
        }),
        tailwindcss(),
    ],
})

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class([
    'dark' => ($appearance ?? 'system') == 'dark',
    'theme-blush-pink' => ($theme ?? 'default') === 'blush-pink',
])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply theme immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';
                const theme = '{{ $theme ?? "default" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }

                if (theme === 'blush-pink') {
                    document.documentElement.classList.add('theme-blush-pink');
                }

                // Generate themed favicon before CSS loads
                var isDark = appearance === 'dark' || (appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                var colors = {
                    'default-light': ['hsl(0 0% 10%)', 'hsl(0 0% 98%)'],
                    'default-dark': ['hsl(360 100% 100%)', 'hsl(0 0% 100%)'],
                    'blush-pink-light': ['hsl(350 80% 42%)', 'hsl(0 0% 100%)'],
                    'blush-pink-dark': ['hsl(350 70% 60%)', 'hsl(0 0% 100%)'],
                };
                var key = theme + '-' + (isDark ? 'dark' : 'light');
                var c = colors[key] || colors['default-light'];
                var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">' +
                    '<rect width="32" height="32" rx="8" fill="' + c[0] + '"/>' +
                    '<g transform="translate(4 4)" fill="none" stroke="' + c[1] + '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
                    '<path d="M17 21a1 1 0 0 0 1-1v-5.35c0-.457.316-.844.727-1.041a4 4 0 0 0-2.134-7.589 5 5 0 0 0-9.186 0 4 4 0 0 0-2.134 7.588c.411.198.727.585.727 1.041V20a1 1 0 0 0 1 1Z"/>' +
                    '<path d="M6 17h12"/>' +
                    '</g></svg>';
                var link = document.querySelector('link[rel="icon"][type="image/svg+xml"]');
                if (link) link.href = 'data:image/svg+xml,' + encodeURIComponent(svg);
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }

            html.theme-blush-pink {
                background-color: oklch(0.98 0.02 350);
            }

            html.theme-blush-pink.dark {
                background-color: oklch(0.12 0.03 350);
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600|caveat:400,500,600,700" rel="stylesheet" />

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>

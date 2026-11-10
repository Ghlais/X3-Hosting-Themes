@php require_once base_path('themes/x3-hosting/lib/Theme.php'); @endphp
@php $x3 = require base_path('themes/x3-hosting/lang/' . (str_starts_with(app()->getLocale(), 'ar') ? 'ar' : 'en') . '/ui.php'); @endphp
<!DOCTYPE html>
<html class="x3-root" lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ in_array(app()->getLocale(), config('app.rtl_locales', ['ar'])) ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>
        {{ theme('brand_name', 'X3 Hosting') }}
        @isset($title)
        - {{ $title }}
        @endisset
    </title>
    @livewireStyles
    @php Vite::useHotFile(public_path('x3-hosting.hot')); @endphp
    @vite(['themes/' . config('settings.theme') . '/js/app.js', 'themes/' . config('settings.theme') . '/css/app.css'], config('settings.theme'))
    @include('layouts.colors')

    @if (config('settings.favicon'))
    <link rel="icon" href="{{ Storage::url(config('settings.favicon')) }}">
    @endif
    @isset($title)
    <meta content="{{ isset($title) ? config('app.name', 'Paymenter') . ' - ' . $title : config('app.name', 'Paymenter') }}" property="og:title">
    <meta content="{{ isset($title) ? config('app.name', 'Paymenter') . ' - ' . $title : config('app.name', 'Paymenter') }}" name="title">
    @endisset
    @isset($description)
    <meta content="{{ $description }}" property="og:description">
    <meta content="{{ $description }}" name="description">
    @endisset
    @isset($image)
    <meta content="{{ $image }}" property="og:image">
    <meta content="{{ $image }}" name="image">
    @endisset

    <meta name="theme-color" content="#0a0a0a">

    {!! hook('head') !!}
</head>

<body data-locale="{{ str_replace('_', '-', app()->getLocale()) }}" data-direction="{{ in_array(app()->getLocale(), config('app.rtl_locales', ['ar'])) ? 'rtl' : 'ltr' }}" data-motion="{{ theme('motion', true) ? 'on' : 'off' }}" class="x3-body w-full bg-background text-base min-h-screen flex flex-col antialiased"
    x-cloak
    x-data="{
        theme: $persist(@js(theme('default_mode', 'dark'))).as('x3_theme_mode'),
        systemDark: window.matchMedia('(prefers-color-scheme: dark)').matches,
        init() {
            this.media = window.matchMedia('(prefers-color-scheme: dark)');
            this.listener = e => { this.systemDark = e.matches; };
            this.media.addEventListener('change', this.listener);
        },
        destroy() { this.media?.removeEventListener('change', this.listener); },
        get isDark() {
            return this.theme === 'dark' || (this.theme === 'system' && this.systemDark);
        }
    }"
    :class="{'dark': isDark}"
>
    {!! hook('body') !!}
    <a class="x3-skip" href="#x3-main">{{ $x3['skip'] }}</a>
    <x-navigation />
    <div class="w-full flex flex-grow">
        @if (isset($sidebar) && $sidebar)
        <x-navigation.sidebar :title="$title ?? ''" />
        @endif
        <div class="{{ (isset($sidebar) && $sidebar) ? 'md:ms-64' : '' }} flex flex-col flex-grow min-w-0">
            <main id="x3-main" tabindex="-1" class="mt-16 grow x3-main">
                {{ $slot }}
            </main>
            <x-notification />
            <x-confirmation />
            <div class="flex">
                <x-navigation.footer />
            </div>
        </div>
        <x-impersonating />
    </div>
    @livewireScriptConfig
    {!! hook('footer') !!}
</body>

</html>

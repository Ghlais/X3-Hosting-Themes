@php
require_once base_path('themes/x3-hosting/lib/Theme.php');
$x3 = \X3\Rackline\Theme::strings(); $lang = \X3\Rackline\Theme::locale();
$buttonUrl = \X3\Rackline\Theme::url(theme('footer_button_url', 'https://paymenter.org/'), 'https://paymenter.org/');
$discord = \X3\Rackline\Theme::url(theme('discord_url', ''));
$support = \X3\Rackline\Theme::url(theme('support_url', ''), config('settings.tickets_disabled', false) ? '' : route('tickets'));
$links = \X3\Rackline\Theme::map('footer_links');
@endphp
<footer class="x3-footer w-full"><div class="container x3-footer-inner">
<div><a href="{{ route('home') }}" wire:navigate class="x3-footer-brand"><x-logo class="w-8" /><span>{{ theme('brand_name', 'X3 Hosting') }}</span></a><p>{{ theme('footer_'.$lang, $x3['footer']) }}</p></div>
<nav aria-label="{{ $x3['support'] }}" class="x3-footer-links">
<a href="{{ route('home') }}#x3-catalog" wire:navigate>{{ $x3['store'] }}</a>
<a href="{{ route('dashboard') }}" wire:navigate>{{ $x3['portal'] }}</a>
@if($support)<a href="{{ $support }}">{{ $x3['support'] }}</a>@endif
@if($discord)<a href="{{ $discord }}" target="_blank" rel="noopener noreferrer">{{ $x3['discord'] }} ↗</a>@endif
@foreach(array_slice($links,0,8) as $link)
@if(is_array($link) && ($href = \X3\Rackline\Theme::url($link['url'] ?? '')) && is_string($label = ($link['label_'.$lang] ?? $link['label_en'] ?? '')) && $label !== '')
<a href="{{ $href }}">{{ $label }}</a>
@endif
@endforeach
</nav>
<a class="x3-footer-powered" href="{{ $buttonUrl }}" target="_blank" rel="noopener noreferrer">{{ theme('footer_button_'.$lang, $lang === 'ar' ? 'يعمل بواسطة Paymenter ↗' : 'Powered by Paymenter ↗') }}</a>
</div></footer>

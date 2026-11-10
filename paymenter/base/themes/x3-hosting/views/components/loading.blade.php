@php $x3 = require base_path('themes/x3-hosting/lang/' . (str_starts_with(app()->getLocale(), 'ar') ? 'ar' : 'en') . '/ui.php'); @endphp
@props([
    'target' => null,
])
<div role="status" 
    @if(!$target) wire:loading @else wire:loading wire:target="{{ $target }}" @endif
>
    <x-ri-loader-5-fill aria-hidden="true" {{ $attributes->merge(['class' => 'size-6 me-2 fill-black animate-spin dark:fill-white']) }} />
    <span class="sr-only">{{ $x3['literal_6'] }}</span>
</div>
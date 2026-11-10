@php $x3 = require base_path('themes/x3-hosting/lang/' . (str_starts_with(app()->getLocale(), 'ar') ? 'ar' : 'en') . '/ui.php'); @endphp
{{-- @deprecated, removed in 1.6 --}}
<x-select
    wire:model.live="currentLocale"
    :options="collect($locales)->map(fn($locale, $code) => [
        'value' => $code,
        'label' => $locale
    ])->values()->toArray()"
    :placeholder="$x3['literal_10']"
/>
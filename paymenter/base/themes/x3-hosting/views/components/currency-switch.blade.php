@php $x3 = require base_path('themes/x3-hosting/lang/' . (str_starts_with(app()->getLocale(), 'ar') ? 'ar' : 'en') . '/ui.php'); @endphp
{{-- @deprecated, removed in 1.6 --}}
<div>
    <strong class="block p-2 text-xs font-semibold uppercase text-base/50"> {{ str_starts_with(app()->getLocale(), 'ar') ? 'العملة' : 'Currency' }} </strong>
    <x-select wire:model.live="currentCurrency" :options="$this->currencies" :placeholder="$x3['literal_9']" />
</div>
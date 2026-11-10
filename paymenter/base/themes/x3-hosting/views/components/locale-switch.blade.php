@php $x3 = require base_path('themes/x3-hosting/lang/' . (str_starts_with(app()->getLocale(), 'ar') ? 'ar' : 'en') . '/ui.php'); @endphp
<x-dropdown>
    <x-slot:trigger>
        <div class="text-sm text-base font-semibold text-nowrap">
            @if(count($locales) > 1)
            {{ config('app.available_locales')[app()->getLocale()] }}
            @endif
            @if(count($locales) > 1 && count($this->currencies) > 1 && Cart::items()->isEmpty())
            <span class="text-base/50 font-semibold">|</span>
            @endif
            @if(count($this->currencies) > 1 && Cart::items()->isEmpty())
            {{ collect($this->currencies)->firstWhere('value', $this->currentCurrency)['label'] ?? $this->currentCurrency }}
            @endif
        </div>
    </x-slot:trigger>
    <x-slot:content>
        @if(count($locales) > 1)
        <div>
            <strong class="block p-2 text-xs font-semibold uppercase text-base/50"> {{ $x3['language'] }} </strong>
            <x-select wire:model.live="currentLocale" :options="collect($locales)->map(fn($code) => [
                'value' => $code,
                'label' => config('app.available_locales')[$code]
                ])->values()->toArray()" :placeholder="$x3['select_language']" />
        </div>
        @endif
        @if(count($this->currencies) > 1 && Cart::items()->isEmpty())
        <div>
            <strong class="block p-2 text-xs font-semibold uppercase text-base/50"> {{ $x3['currency'] }} </strong>
            <x-select wire:model.live="currentCurrency" :options="$this->currencies" :placeholder="$x3['select_currency']" />
        </div>
        @endif
    </x-slot:content>
</x-dropdown>

@php $x3 = require base_path('themes/x3-hosting/lang/' . (str_starts_with(app()->getLocale(), 'ar') ? 'ar' : 'en') . '/ui.php'); @endphp
<button 
    {{ $attributes->merge(['class' => 'flex items-center gap-2 justify-center bg-primary text-inverted text-sm font-semibold hover:bg-primary/80 py-2.5 lg:py-2 px-4.5 rounded-md w-full duration-300 cursor-pointer disabled:cursor-not-allowed disabled:opacity-50']) }}>
    @if (isset($type) && $type === 'submit')
        <div role="status" wire:loading>
            <x-ri-loader-5-fill aria-hidden="true" class="size-6 me-2 fill-background animate-spin" />
            <span class="sr-only">{{ $x3['literal_6'] }}</span>
        </div>
        <div wire:loading.remove>
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</button>

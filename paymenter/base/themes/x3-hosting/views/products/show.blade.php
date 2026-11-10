@php require_once base_path('themes/x3-hosting/lib/Theme.php'); $card=\X3\Rackline\Theme::card($product); @endphp
@php $x3 = require base_path('themes/x3-hosting/lang/' . (str_starts_with(app()->getLocale(), 'ar') ? 'ar' : 'en') . '/ui.php'); @endphp
<div class="container mt-14 x3-product">
    <div class="flex flex-col @if ($product->image) md:grid grid-cols-2 gap-16 bg-background-secondary hover:bg-background-secondary/80 border border-neutral p-4 rounded-lg @endif">
        @if ($product->image)
        <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
            class="w-full h-96 object-contain object-center rounded-md">
        @endif
        <p class="x3-eyebrow">{{ $x3['product'] }}</p>
        <div class="flex flex-col">
            @if ($product->stock === 0)
            <span class="text-xs font-medium me-2 px-2.5 py-0.5 rounded bg-red-900 text-red-300 w-fit mb-3">
                {{ __('product.out_of_stock', ['product' => $product->name]) }}
            </span>
            @elseif($product->stock > 0)
            <span class="text-xs font-medium me-2 px-2.5 py-0.5 rounded bg-green-900 text-green-300 w-fit mb-3">
                {{ __('product.in_stock') }}
            </span>
            @endif
            <div class="flex flex-row justify-between">
                <div>
                    <h2 class="text-3xl font-bold">{{ $product->name }}</h2>
                    <h3 class="text-xl font-semibold">
                        <bdi>{{ $card['price'] }}</bdi><span class="x3-item-cycle block">{{ $card['cycle'] }}</span>
                    </h3>
                </div>
                @if ($product->stock !== 0 && $product->price()->available)
                <a href="{{ $card['checkout'] }}" wire:navigate aria-label="{{ $x3['configure'] }}"><x-ri-arrow-right-up-line class="size-7" /></a>
                @endif
            </div>
            @if($card['recommended'])<span class="x3-badge w-fit my-3">{{ $x3['recommended'] }}</span>@endif
            @if($card['specs'])<dl class="x3-specs">@foreach($card['specs'] as $key=>$value)<div><dt>{{ $x3[$key] }}</dt><dd><bdi>{{ $value }}</bdi></dd></div>@endforeach</dl>@endif
            <div class="x3-product-cycles"><h3>{{ $x3['cycle'] }}</h3>
            @foreach($product->availablePlans() as $availablePlan)
            @php $optionPrice=$availablePlan->price(); @endphp
            <div><span>{{ $availablePlan->name }} · {{ \X3\Rackline\Theme::cycle($availablePlan) }}</span><div><bdi>{{ $optionPrice->is_free ? $x3['free'] : $optionPrice->formatted->price }}</bdi>@if($optionPrice->has_setup_fee)<small>{{ $x3['setup'] }}: <bdi>{{ $optionPrice->formatted->setup_fee }}</bdi></small>@endif</div></div>
            @endforeach</div>
            <p class="x3-plan-note mt-2">{{ $x3['plan_price_note'] }}</p>
            <article class="my-4 prose dark:prose-invert">
                {!! $product->description !!}
            </article>

            @if ($product->stock !== 0 && $product->price()->available)
            <a href="{{ $card['checkout'] }}"
                wire:navigate>
                <x-button.primary>{{ __('product.add_to_cart') }}</x-button.primary>
            </a>
            @endif
        </div>
    </div>
</div>

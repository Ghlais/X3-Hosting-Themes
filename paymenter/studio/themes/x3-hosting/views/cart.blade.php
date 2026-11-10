@php require_once base_path('themes/x3-hosting/lib/Purchase.php'); @endphp
@php $x3 = require base_path('themes/x3-hosting/lang/' . (str_starts_with(app()->getLocale(), 'ar') ? 'ar' : 'en') . '/ui.php'); @endphp
@php $pui=\X3\Rackline\Purchase::strings(); @endphp
<div class="container x3-studio-route x3-cart"><x-x3.purchase-assets /><x-x3.studio-panel :title="$pui['review']" variant="billing" :description="$pui['review_note']" /><x-x3.progress :step="2" />
    <div class="x3-purchase-grid">
        <div class="x3-cart-items">
            @if (Cart::items()->count() === 0)
            <h2 class="text-2xl font-semibold">
                {{ __('product.empty_cart') }}
<a href="{{ route('home') }}" wire:navigate class="x3-text-link block mt-4 text-sm">{{ $x3['empty_cart_cta'] }} ↗</a>
            </h2>
            @endif
            @foreach (Cart::items() as $item)
            <div class="x3-cart-item">
                <div class="flex flex-col gap-1">
                    <h2 class="text-2xl font-semibold">
                        {{ $item->product->name }}
                    </h2>
                    <p class="x3-item-cycle">{{ \X3\Rackline\Theme::cycle($item->plan) }}</p>
                    <dl class="x3-summary-lines x3-cart-item-summary"><div><dt>{{ $x3['setup_once'] }}</dt><dd><bdi>{{ $item->price->format($item->price->setup_fee * $item->quantity) }}</bdi></dd></div>
                    @if($item->plan->type === 'recurring')<div><dt>{{ $x3['renewal'] }}</dt><dd><bdi>{{ $item->price->format((($item->cart->coupon && !$item->cart->coupon->recurring) ? $item->price->original_price : $item->price->price) * $item->quantity) }}</bdi></dd></div>@endif</dl>
                    <p class="text-sm">
                        @foreach ($item->config_options as $option)
                        {{ $option['option_name'] }}: {{ $option['value_name'] }}<br>
                        @endforeach
                    </p>
                </div>
                <div class="x3-cart-item-actions">
                    <h3 class="text-xl font-semibold p-1">
                        {{ $item->price->format($item->price->total * $item->quantity) }} @if ($item->quantity > 1)
                        ({{ $item->price }} {{ $x3['literal_17'] }})
                        @endif
                    </h3>
                    <div class="x3-cart-controls">
                        @if ($item->product->allow_quantity == 'combined')
                        <div class="flex flex-row gap-1 items-center mr-4">
                            <x-button.secondary
                                wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity - 1 }})"
                                class="h-full !w-fit">
                                -
                            </x-button.secondary>
                            <x-form.input class="h-10 text-center" disabled divClass="!mt-0 !w-14" value="{{ $item->quantity }}" name="quantity" />
                            <x-button.secondary
                                wire:click="updateQuantity({{ $item->id }}, {{ $item->quantity + 1 }});"
                                class="h-full !w-fit">
                                +
                            </x-button.secondary>
                        </div>
                        @endif
                        <a href="{{ route('products.checkout', [$item->product->category, $item->product, 'edit' => $item->id]) }}"
                            wire:navigate>
                            <x-button.primary class="h-fit w-fit">
                                {{ __('product.edit') }}
                            </x-button.primary>
                        </a>
                        <x-button.danger wire:click="removeProduct({{ $item->id }})" class="h-fit !w-fit">
                            <x-loading target="removeProduct({{ $item->id }})" />
                            <div wire:loading.remove wire:target="removeProduct({{ $item->id }})">
                                {{ __('product.remove') }}
                            </div>
                        </x-button.danger>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="x3-buy-sidebar">
            @if (Cart::items()->count() > 0)
            <div class="x3-order-card">
                <h2 class="text-2xl font-semibold mb-3">
                    {{ __('product.order_summary') }}
                </h2>
                <div class="font-semibold flex items-end gap-2">
                    @if(!$coupon)
                    <x-form.input wire:model="coupon" name="coupon" :label="$x3['literal_2']" />
                    <x-button.primary wire:click="applyCoupon" class="h-fit !w-fit mb-0.5" wire:loading.attr="disabled">
                        <x-loading target="applyCoupon" />
                        <div wire:loading.remove wire:target="applyCoupon">
                            {{ __('product.apply') }}
                        </div>
                    </x-button.primary>
                    @else
                    <div class="flex justify-between items-center w-full">
                        <h4 class="text-center w-full">{{ $coupon->code }}</h4>
                        <x-button.secondary wire:click="removeCoupon" class="h-fit !w-fit">
                            {{ __('product.remove') }}
                        </x-button.secondary>
                    </div>
                    @endif
                </div>
                <div class="font-semibold flex justify-between">
                    <h4>{{ __('invoices.subtotal') }}:</h4> {{ $total->format($total->subtotal) }}
                </div>
                @if ($total->tax > 0)
                <div class="font-semibold flex justify-between">
                    <h4>{{ \App\Classes\Settings::tax()->name }} ({{ \App\Classes\Settings::tax()->rate }}%):</h4> {{ $total->format($total->tax) }}
                </div>
                @endif
                <div class="x3-cart-total">
                    <h4>{{ __('invoices.total') }}:</h4> {{ $total->format($total->total) }}
                </div>

                <p class="x3-plan-note">{{ $x3['cart_renewal_note'] }}</p>
                <div class="flex flex-col gap-2 w-full col-span-1">
                    @if(config('settings.tos'))
                    <x-form.checkbox wire:model="tos" name="tos">
                        {{ __('product.tos') }}
                        <a href="{{ config('settings.tos') }}" target="_blank" class="text-primary hover:text-primary/80">
                            {{ __('product.tos_link') }}
                        </a>
                    </x-form.checkbox>
                    @endif

                    <div class="flex flex-row justify-end gap-2">
                        <x-button.primary wire:click="checkout" class="x3-buy-submit" wire:loading.attr="disabled">
                            <x-loading target="checkout" />
                            <div wire:loading.remove wire:target="checkout">
                                {{ __('product.checkout') }}
                            </div>
                        </x-button.primary>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

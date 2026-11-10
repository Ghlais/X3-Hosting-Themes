@php
require_once base_path('themes/x3-hosting/lib/Purchase.php');
$pui=\X3\Rackline\Purchase::strings();$x3=\X3\Rackline\Theme::strings();$specs=\X3\Rackline\Purchase::specs($product);
@endphp
<div class="container x3-studio-route x3-checkout">
<x-x3.purchase-assets />
<a class="x3-buy-back" href="{{ route('products.show',[$product->category,$product]) }}" wire:navigate>↗ {{ $pui['back'] }}</a>
<x-x3.studio-panel :title="$pui['configure']" variant="store" :description="$pui['configure_note']" />
<x-x3.progress :step="1" />
<div class="x3-purchase-grid"><div class="x3-purchase-fields">
    <section class="x3-config-card"><div class="x3-config-product">@if($product->image)<img src="{{ Storage::url($product->image) }}" alt="">@endif<div><p class="x3-buy-kicker">{{ $product->category->name }}</p><h2>{{ $product->name }}</h2></div></div>
    <fieldset class="x3-cycle-field"><legend>{{ $pui['choose_cycle'] }}</legend>
    @foreach($product->availablePlans() as $availablePlan)
    @php $optionPrice=$availablePlan->price(); @endphp
    <label class="x3-cycle-option {{ (string)$plan_id===(string)$availablePlan->id ? 'is-selected' : '' }}"><input type="radio" name="plan_id" wire:model.live="plan_id" value="{{ $availablePlan->id }}"><span><strong>{{ $availablePlan->name }}</strong><small>{{ \X3\Rackline\Theme::cycle($availablePlan) }}</small></span><bdi>{{ $optionPrice->is_free ? $x3['free'] : $optionPrice->formatted->price }}</bdi></label>
    @endforeach
    @error('plan_id')<p class="x3-buy-error">{{ $message }}</p>@enderror
    </fieldset></section>
    <section class="x3-config-card x3-native-options"><div class="x3-buy-section-head"><span class="x3-buy-index" aria-hidden="true">01</span><h2>{{ $pui['options'] }}</h2></div><p class="x3-buy-micro">{{ $pui['options_note'] }}</p>
    @if($product->configOptions->isEmpty() && count($this->getCheckoutConfig())===0)<p class="x3-no-options">{{ $pui['no_options'] }}</p>@endif
            @foreach ($product->configOptions as $configOption)
            @php
                $showPriceTag = $configOption->children->filter(fn ($value) => !$value->price(billing_period: $plan->billing_period, billing_unit: $plan->billing_unit)->is_free)->count() > 0;
            @endphp
            <x-form.configoption :config="$configOption" :name="'configOptions.' . $configOption->id" :showPriceTag="$showPriceTag" :plan="$plan">
                @if ($configOption->type == 'select')
                    @foreach ($configOption->children as $configOptionValue)
                        <option value="{{ $configOptionValue->id }}">
                            {{ $configOptionValue->name }}
                            {{ ($showPriceTag && $configOptionValue->price(billing_period: $plan->billing_period, billing_unit: $plan->billing_unit)->available) ? ' - ' . $configOptionValue->price(billing_period: $plan->billing_period, billing_unit: $plan->billing_unit) : '' }}
                        </option>
                    @endforeach
                @elseif($configOption->type == 'radio')
                    @foreach ($configOption->children as $configOptionValue)
                        <div class="flex items-center gap-2">
                            <input type="radio" id="{{ $configOptionValue->id }}" name="{{ $configOption->id }}"
                                wire:model.live="configOptions.{{ $configOption->id }}"
                                value="{{ $configOptionValue->id }}" />
                            <label for="{{ $configOptionValue->id }}">
                                {{ $configOptionValue->name }}
                                {{ ($showPriceTag && $configOptionValue->price(billing_period: $plan->billing_period, billing_unit: $plan->billing_unit)->available) ? ' - ' . $configOptionValue->price(billing_period: $plan->billing_period, billing_unit: $plan->billing_unit) : '' }}
                            </label>
                        </div>
                    @endforeach
                @endif
            </x-form.configoption>
        @endforeach
        @foreach ($this->getCheckoutConfig() as $configOption)
            @php $configOption = (object) $configOption; @endphp
            <x-form.configoption :config="$configOption" :name="'checkoutConfig.' . $configOption->name">
                @if ($configOption->type == 'select')
                    @foreach ($configOption->options as $configOptionValue => $configOptionValueName)
                        <option value="{{ $configOptionValue }}">
                            {{ $configOptionValueName }}
                        </option>
                    @endforeach
                @elseif($configOption->type == 'radio')
                    @foreach ($configOption->options as $configOptionValue => $configOptionValueName)
                        <div class="flex items-center gap-2">
                            <input type="radio" id="{{ $configOptionValue }}" name="{{ $configOption->name }}"
                                wire:model.live="checkoutConfig.{{ $configOption->name }}"
                                value="{{ $configOptionValue }}" />
                            <label for="{{ $configOptionValue }}">
                                {{ $configOptionValueName }}
                            </label>
                        </div>
                    @endforeach
                @endif
            </x-form.configoption>
        @endforeach

    </section>
</div>
<aside class="x3-buy-sidebar"><section class="x3-order-card"><p class="x3-buy-kicker">X3 HOSTING / ORDER</p><h2>{{ $pui['order'] }}</h2><p class="x3-order-product">{{ $product->name }}</p>
<dl class="x3-buy-lines"><div><dt>{{ $pui['selected'] }}</dt><dd>{{ \X3\Rackline\Theme::cycle($plan) }}</dd></div><div><dt>{{ $pui['configured_price'] }}</dt><dd><bdi>{{ $total->format($total->price) }}</bdi></dd></div><div><dt>{{ $pui['setup'] }}</dt><dd><bdi>{{ $total->format($total->setup_fee) }}</bdi></dd></div>
@if($total->total_tax>0)<div><dt>{{ __('invoices.subtotal') }}</dt><dd><bdi>{{ $total->format($total->subtotal) }}</bdi></dd></div><div><dt>{{ \App\Classes\Settings::tax()->name }} ({{ \App\Classes\Settings::tax()->rate }}%)</dt><dd><bdi>{{ $total->formatted->total_tax }}</bdi></dd></div>@endif</dl>
<div class="x3-order-total" aria-live="polite"><span>{{ $pui['due_today'] }}</span><strong><bdi>{{ $total }}</bdi></strong></div>
@if($plan->type==='recurring')<p class="x3-order-renewal">{{ $pui['renewal'] }} <bdi>{{ $total->format($total->price) }}</bdi> · {{ \X3\Rackline\Theme::cycle($plan) }}</p>@else<p class="x3-order-renewal">{{ $pui['no_renewal'] }}</p>@endif
@if($product->stock!==0 && $total->available)<x-button.primary class="x3-buy-submit" wire:click="checkout" wire:loading.attr="disabled"><x-loading target="checkout" /><span wire:loading.remove wire:target="checkout">{{ __('product.add_to_cart') }} ↗</span></x-button.primary>@endif
<p class="x3-buy-micro">{{ $pui['options_final'] }}</p>
</section></aside></div></div>

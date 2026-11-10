@php require_once base_path('themes/x3-hosting/lib/Theme.php'); @endphp
@php $x3 = require base_path('themes/x3-hosting/lang/' . (str_starts_with(app()->getLocale(), 'ar') ? 'ar' : 'en') . '/ui.php'); @endphp
<div class="container mt-14 flex flex-col md:grid md:grid-cols-4 gap-6"><header class="x3-page-heading col-span-full"><p class="x3-eyebrow">X3 HOSTING / RACKLINE</p><h1>{{ $x3['configure'] }}</h1><p>{{ $x3['checkout_note'] }}</p></header><x-x3.progress :step="1" />
    <div class="flex flex-col gap-4 w-full col-span-3">
        <h1 class="text-3xl font-bold">{{ $product->name }}</h1>
        <div class="flex flex-row w-full gap-4">
            @if ($product->image)
                <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}" class="max-w-40">
            @endif
            <div class="max-h-28 overflow-y-auto w-full">
                <article class="prose dark:prose-invert prose-sm">
                    {!! $product->description !!}
                </article>
            </div>
        </div>
        @if ($product->availablePlans()->count() > 1)
            <x-form.select wire:model.live="plan_id" name="plan_id" :label="$x3['literal_0']">
                @foreach ($product->availablePlans() as $availablePlan)
                    <option value="{{ $availablePlan->id }}">
                        {{ $availablePlan->name }} -
                        {{ $availablePlan->price()->formatted->price }}
                        @if ($availablePlan->price()->has_setup_fee)
                            + {{ $availablePlan->price()->formatted->setup_fee }} {{ __('product.setup_fee') }}
                        @endif
                    </option>
                @endforeach
            </x-form.select>
        @endif

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
    </div>
    <div class="flex flex-col gap-2 w-full col-span-1 bg-background-secondary p-3 rounded-md h-fit">
        <h2 class="text-2xl font-semibold  mb-2">
            {{ __('product.order_summary') }}
        </h2>
        <dl class="x3-summary-lines">
            <div><dt>{{ $x3['cycle'] }}</dt><dd>{{ \X3\Rackline\Theme::cycle($plan) }}</dd></div>
            @if($plan->type === 'recurring')<div><dt>{{ $x3['renewal'] }}</dt><dd><bdi>{{ $total->format($total->price) }}</bdi></dd></div>@else<div><dt>{{ $x3['no_renewal'] }}</dt><dd>—</dd></div>@endif
            <div><dt>{{ $x3['setup_once'] }}</dt><dd><bdi>{{ $total->format($total->setup_fee) }}</bdi></dd></div>
        </dl>
        @if ($total->total_tax > 0)
            <div class="font-semibold flex justify-between">
                <h4>{{ __('invoices.subtotal') }}:</h4> {{ $total->format($total->subtotal) }}
            </div>
            <div class="font-semibold flex justify-between">
                <h4>{{ \App\Classes\Settings::tax()->name }} ({{ \App\Classes\Settings::tax()->rate }}%):</h4> {{ $total->formatted->total_tax }}
            </div>
        @endif
        <div class="text-lg font-semibold flex justify-between">
            <h4>{{ __('product.total_today') }}:</h4> {{ $total }}
        </div>
        @if (($product->stock > 0 || !$product->stock) && $product->price()->available)
            <div>
                <x-button.primary wire:click="checkout" wire:loading.attr="disabled">
                    <x-loading target="checkout" />
                    <div wire:loading.remove wire:target="checkout">
                        {{ __('product.checkout') }}
                    </div>
                </x-button.primary>
            </div>
        @endif
        <p class="x3-plan-note">{{ $x3['purchase_note'] }}</p>
    </div>
</div>

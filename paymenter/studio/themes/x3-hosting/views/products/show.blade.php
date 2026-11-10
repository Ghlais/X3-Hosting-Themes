@php
require_once base_path('themes/x3-hosting/lib/Purchase.php');
$pui=\X3\Rackline\Purchase::strings();$x3=\X3\Rackline\Theme::strings();
$plans=\X3\Rackline\Purchase::plans($product);$selected=\X3\Rackline\Purchase::selected($product,$plans);
$specs=\X3\Rackline\Purchase::specs($product);$card=\X3\Rackline\Theme::card($product);
$intro=\X3\Rackline\Purchase::intro($product);$support=\X3\Rackline\Purchase::support();
@endphp
<div class="container x3-studio-route x3-buy" x-data="{plans: @js($plans), planId: @js($selected['id'] ?? ''), get choice() { return this.plans.find(p => p.id === this.planId) || null; }}">
    <x-x3.purchase-assets />
    <nav class="x3-buy-crumbs" aria-label="{{ $pui['home'] }}"><a href="{{ route('home') }}" wire:navigate>{{ $pui['home'] }}</a><span aria-hidden="true">/</span><a href="{{ route('category.show',$product->category) }}" wire:navigate>{{ $product->category->name }}</a><span aria-hidden="true">/</span><span>{{ $product->name }}</span></nav>
    <div class="x3-buy-layout">
        <div class="x3-buy-content">
            <header class="x3-buy-hero">
                <div class="x3-buy-cover">
                    @if($product->image)<img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}">@else<x-x3.hardware :variant="\X3\Rackline\Theme::variant($product->category)" />@endif
                </div>
                <div><p class="x3-buy-kicker">{{ $pui['collection'] }}</p><h1>{{ $product->name }}</h1>
                @if($intro)<p class="x3-buy-intro">{{ $intro }}</p>@endif
                <div class="x3-buy-badges"><span class="x3-buy-badge"><span class="x3-status-dot" aria-hidden="true"></span>{{ $product->stock===0 ? $pui['out_of_stock'] : ($selected ? $pui['available'] : $pui['unavailable']) }}</span>@if($card['recommended'])<span class="x3-buy-badge x3-buy-recommended">{{ $x3['recommended'] }}</span>@endif</div></div>
            </header>
            @if($specs)
            <section class="x3-resource-section" aria-labelledby="x3-resources"><h2 id="x3-resources" class="x3-buy-section-title">{{ $pui['specifications'] }}</h2>
            <dl class="x3-resource-grid">@foreach($specs as $key=>$value)<div><dt><span class="x3-resource-glyph" aria-hidden="true">{{ ['cpu'=>'▥','ram'=>'▤','storage'=>'▱','network'=>'↗','location'=>'⌖'][$key] }}</span>{{ $pui[$key] }}</dt><dd><bdi>{{ $value }}</bdi></dd></div>@endforeach</dl></section>
            @endif
            <section class="x3-buy-description" aria-labelledby="x3-about"><div class="x3-buy-section-head"><span class="x3-buy-index" aria-hidden="true">01</span><h2 id="x3-about">{{ $pui['description'] }}</h2></div>
            <article class="x3-purchase-rich prose dark:prose-invert">{!! \X3\Rackline\Purchase::description($product->description) !!}@if(!trim(\X3\Rackline\Purchase::text($product->description)))<p>{{ $pui['empty_description'] }}</p>@endif</article></section>
            <section class="x3-buy-billing" aria-labelledby="x3-billing"><div class="x3-buy-section-head"><span class="x3-buy-index" aria-hidden="true">02</span><h2 id="x3-billing">{{ $pui['billing'] }}</h2></div><p>{{ $pui['billing_note'] }}</p><p class="x3-buy-micro">{{ $pui['price_note'] }}</p></section>
        </div>
        <aside class="x3-buy-sidebar" aria-label="{{ $pui['purchase'] }}">
            <section class="x3-order-card"><p class="x3-buy-kicker">X3 HOSTING / ORDER</p><h2>{{ $pui['configuration'] }}</h2>
            @if($selected)
                <p class="x3-buy-price"><bdi x-text="choice?.price">{{ $selected['price'] }}</bdi></p><p class="x3-buy-cycle" x-text="choice?.cycle">{{ $selected['cycle'] }}</p>
                <fieldset class="x3-cycle-field"><legend>{{ $pui['choose_cycle'] }}</legend>
                @foreach($plans as $option)<label class="x3-cycle-option" :class="{'is-selected':planId===@js($option['id'])}"><input type="radio" name="x3-billing-plan" value="{{ $option['id'] }}" x-model="planId" @checked($selected['id']===$option['id'])><span><strong>{{ $option['name'] }}</strong><small>{{ $option['cycle'] }}</small></span><bdi>{{ $option['price'] }}</bdi></label>@endforeach
                </fieldset>
                <dl class="x3-buy-lines"><div><dt>{{ $pui['setup'] }}</dt><dd><bdi x-text="choice?.setup">{{ $selected['setup'] }}</bdi></dd></div><div class="x3-buy-line-total"><dt>{{ $pui['initial'] }}</dt><dd><bdi x-text="choice?.initial">{{ $selected['initial'] }}</bdi></dd></div></dl>
                @if($selected['checkout'])<a class="x3-buy-submit" href="{{ $selected['checkout'] }}" :href="choice?.checkout" wire:navigate>{{ $pui['continue'] }}<span aria-hidden="true">↗</span></a>@else<p class="x3-buy-unavailable" role="status">{{ $pui['out_of_stock'] }}</p>@endif
                <p class="x3-buy-micro">{{ $pui['price_note'] }}</p>
            @else<p class="x3-buy-unavailable" role="status">{{ $pui['unavailable'] }}</p>@endif
            </section>
            @if($support)<section class="x3-buy-help"><span aria-hidden="true">↗</span><div><h3>{{ $pui['help_title'] }}</h3><p>{{ $pui['help_text'] }}</p><a href="{{ $support }}">{{ $pui['help_link'] }} ↗</a></div></section>@endif
        </aside>
    </div>
    @if($selected && $selected['checkout'])<div class="x3-buy-mobile" role="region" aria-label="{{ $pui['purchase'] }}"><div><bdi x-text="choice?.price">{{ $selected['price'] }}</bdi><small x-text="choice?.cycle">{{ $selected['cycle'] }}</small></div><a class="x3-buy-submit" href="{{ $selected['checkout'] }}" :href="choice?.checkout" wire:navigate>{{ $pui['continue'] }} <span aria-hidden="true">↗</span></a></div>@endif
</div>

@props(['product', 'compare' => false])
@php
require_once base_path('themes/x3-hosting/lib/Theme.php');
$t = \X3\Rackline\Theme::strings(); $card = \X3\Rackline\Theme::card($product);
@endphp
<article class="x3-plan-card x3-product-card {{ $card['recommended'] ? 'x3-recommended' : '' }}" data-product-id="{{ $product->id }}">
<div class="x3-product-kicker"><span>{{ $product->category->name }}</span>@if($card['recommended'])<span class="x3-badge">{{ $t['recommended'] }}</span>@endif</div>
@if($product->image)<img loading="lazy" class="x3-product-image" src="{{ Storage::url($product->image) }}" alt="">@endif
<h3><a href="{{ $card['url'] }}" wire:navigate>{{ $product->name }}</a></h3>
<div class="x3-price-block"><strong><bdi>{{ $card['price'] }}</bdi></strong><span>{{ $card['cycle'] }}</span></div>
<p class="x3-setup">{{ $t['setup'] }}: <bdi>{{ $card['setup'] }}</bdi></p>
@if($card['specs'])<dl class="x3-specs">@foreach($card['specs'] as $key => $value)<div><dt>{{ $t[$key] }}</dt><dd><bdi>{{ $value }}</bdi></dd></div>@endforeach</dl>@endif
<p class="x3-plan-note">{{ $t['plan_price_note'] }}</p>
@if($product->stock === 0)<p class="x3-badge">{{ $t['out_of_stock'] }}</p>@endif
<a class="x3-cta" href="{{ theme('direct_checkout', false) && $card['checkout'] ? $card['checkout'] : $card['url'] }}" wire:navigate>{{ $t['view_product'] }} <span aria-hidden="true">↗</span></a>
@if($compare && theme('comparison_enabled', true))<button type="button" class="x3-compare-toggle" @click="toggle(@js($card['id']))" :aria-pressed="has(@js($card['id']))" x-text="has(@js($card['id'])) ? @js($t['compare_remove']) : @js($t['compare_add'])">{{ $t['compare_add'] }}</button>@endif
</article>

@php $t = \X3\Rackline\Theme::strings(); @endphp
<div class="x3-compare-shell">
<p role="status" aria-live="polite" class="x3-compare-message" x-text="message"></p>
<div class="x3-compare-tray" x-show="selected.length" x-cloak><span><b x-text="selected.length"></b> {{ $t['selected'] }}</span>
<button type="button" class="x3-cta" @click="openComparison()">{{ $t['compare_open'] }} ↗</button>
<button type="button" class="x3-text-link" @click="clear()">{{ $t['clear'] }}</button></div>
<dialog x-ref="comparison" class="x3-comparison" aria-labelledby="x3-compare-title" @click="if ($event.target === $el) $el.close()">
<header><div><p class="x3-eyebrow">X3 / RACKLINE</p><h2 id="x3-compare-title">{{ $t['compare'] }}</h2></div><button type="button" class="x3-dialog-close" @click="$refs.comparison.close()" aria-label="{{ $t['close'] }}">×</button></header>
<p class="x3-plan-note">{{ $t['compare_hint'] }}</p><div class="x3-comparison-scroll" tabindex="0" role="region" aria-label="{{ $t['compare'] }}">
<table><caption class="sr-only">{{ $t['compare_hint'] }}</caption><thead><tr><th scope="col">{{ $t['specification'] }}</th><template x-for="item in items" :key="item.id"><th scope="col" x-text="item.name"></th></template></tr></thead>
<tbody>
@foreach(['price'=>'price','cycle'=>'cycle','setup'=>'setup','stock'=>'available','cpu'=>'cpu','ram'=>'ram','storage'=>'storage','network'=>'network','location'=>'location'] as $row => $label)
<tr><th scope="row">{{ $row === 'price' ? __('product.price') : $t[$label] }}</th><template x-for="item in items" :key="item.id"><td><bdi x-text="{{ in_array($row,['cpu','ram','storage','network','location']) ? 'item.specs.'. $row .' || missing' : 'item.'. $row }}"></bdi></td></template></tr>
@endforeach
<tr><th scope="row">{{ $t['view_product'] }}</th><template x-for="item in items" :key="item.id"><td><a class="x3-text-link" :href="item.url">{{ $t['view_product'] }} ↗</a></td></template></tr>
</tbody></table></div></dialog></div>

@php $x3 = require base_path('themes/x3-hosting/lang/' . (str_starts_with(app()->getLocale(), 'ar') ? 'ar' : 'en') . '/ui.php'); @endphp
@php require_once base_path('themes/x3-hosting/lib/Theme.php'); $featured = \X3\Rackline\Theme::featured(); $faqs = \X3\Rackline\Theme::faq(); @endphp
<div class="x3-home">
@php $lang = str_starts_with(app()->getLocale(), 'ar') ? 'ar' : 'en'; @endphp
@if(theme('announcement_' . $lang, ''))<div class="x3-announcement">{{ theme('announcement_' . $lang) }}</div>@endif
<section class="x3-hero container"><div class="x3-hero-copy"><p class="x3-eyebrow" dir="ltr">{{ $x3['eyebrow'] }}</p>
<h1>{{ theme('hero_title_' . $lang, $x3['hero_title']) }}</h1><p class="x3-hero-description">{{ theme('hero_text_' . $lang, $x3['hero_text']) }}</p>
<div class="x3-hero-actions"><a href="#x3-catalog" class="x3-cta">{{ $x3['store'] }} <span aria-hidden="true">↗</span></a>
<a href="{{ auth()->check() ? route('dashboard') : route('login') }}" wire:navigate class="x3-text-link">{{ $x3['portal'] }} <span aria-hidden="true">↗</span></a></div></div><x-x3.rack /></section>
<div class="x3-process"><div class="container"><span><b>01</b> {{ $x3['step1'] }}</span><span><b>02</b> {{ $x3['step2'] }}</span><span><b>03</b> {{ $x3['step3'] }}</span></div></div>
<section id="x3-catalog" class="container x3-catalog"><header class="x3-section-head"><div><p class="x3-eyebrow">{{ $x3['category'] }}</p><h2>{{ $x3['catalog'] }}</h2></div><p>{{ $x3['catalog_text'] }}</p></header>
<div class="x3-category-grid">@forelse ($categories as $category)
<article class="x3-category-card"><div class="x3-card-top"><span class="x3-index">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><x-ri-server-line class="size-6" /></div>
@if($category->image)<img loading="lazy" src="{{ Storage::url($category->image) }}" alt="" class="{{ theme('small_images', true) ? 'x3-category-image' : 'x3-category-image-large' }}">@endif
<x-x3.hardware :variant="\X3\Rackline\Theme::variant($category)" /><h3>{{ $category->name }}</h3>@if(theme('show_category_description', true))<article class="prose dark:prose-invert x3-description">{!! $category->description !!}</article>@endif
<a href="{{ route('category.show', ['category' => $category->slug]) }}" wire:navigate class="x3-card-link">{{ $x3['view_plans'] }} <span aria-hidden="true">↗</span></a></article>
@empty<div class="x3-empty"><x-ri-server-line class="size-8" /><h3>{{ $x3['empty'] }}</h3><p>{{ $x3['empty_text'] }}</p></div>@endforelse</div></section>

@if($featured->isNotEmpty())
<section class="container x3-featured"><header class="x3-section-head"><div><p class="x3-eyebrow">X3 / PLANS</p><h2>{{ $x3['featured'] }}</h2></div><p>{{ $x3['featured_note'] }}</p></header>
<div class="x3-featured-grid">@foreach($featured as $product)<x-x3.plan-card :product="$product" />@endforeach</div></section>
@endif
@if(theme('faq_enabled', true) && $faqs)
<section class="container x3-faq"><header class="x3-section-head"><div><p class="x3-eyebrow">X3 / FAQ</p><h2>{{ $x3['faq_title'] }}</h2></div><p>{{ $x3['faq_note'] }}</p></header>
<div class="x3-faq-list">@foreach($faqs as $faq)<details><summary>{{ $faq['q'] }}<span aria-hidden="true">+</span></summary><p>{{ $faq['a'] }}</p></details>@endforeach</div></section>
@endif
{!! hook('pages.home') !!}</div>

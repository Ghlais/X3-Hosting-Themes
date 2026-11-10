@php
    require_once base_path('themes/x3-hosting/lib/Theme.php');
    $x3 = \X3\Rackline\Theme::strings();
    $studioLang = \X3\Rackline\Theme::locale();
    $studioFeatured = \X3\Rackline\Theme::featured();
    $studioFaqs = \X3\Rackline\Theme::faq();
@endphp
<div class="x3-home x3-studio-home">
    @if(theme('announcement_' . $studioLang, ''))<div class="x3-announcement">{{ theme('announcement_' . $studioLang) }}</div>@endif
    <section class="container x3-studio-hero">
        <div class="x3-studio-hero-copy">
            <p class="x3-studio-kicker" dir="ltr"><span class="x3-studio-pin"></span>{{ theme('brand_name', 'X3 Hosting') }} / RACKLINE STUDIO</p>
            <h1>{{ theme('hero_title_' . $studioLang, $x3['hero_title']) }}</h1>
            <p class="x3-studio-hero-note">{{ theme('hero_text_' . $studioLang, $x3['hero_text']) }}</p>
            <div class="x3-studio-hero-actions">
                <a href="#x3-catalog" class="x3-cta">{{ $x3['store'] }} <span aria-hidden="true">↗</span></a>
                <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" wire:navigate class="x3-studio-secondary">{{ $x3['portal'] }} <span aria-hidden="true">↗</span></a>
            </div>
        </div>
        <x-x3.studio-scene />
    </section>
    <div class="container">
        <ol class="x3-studio-flow">
            @foreach(['step1','step2','step3'] as $studioStep)
                <li><span aria-hidden="true">{{ str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }}</span>{{ $x3[$studioStep] }}</li>
            @endforeach
        </ol>
    </div>
    <section id="x3-catalog" class="container x3-catalog">
        <header class="x3-section-head"><div><p class="x3-studio-kicker">{{ $x3['category'] }}</p><h2>{{ $x3['catalog'] }}</h2></div><p>{{ $x3['catalog_text'] }}</p></header>
        <div class="x3-category-grid">
            @forelse($categories as $category)
                <article class="x3-category-card x3-studio-category">
                    <div class="x3-card-top"><span class="x3-studio-index">{{ str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }}</span><x-ri-server-line class="size-5" /></div>
                    <div class="x3-studio-category-stage">
                        @if($category->image)<img loading="lazy" src="{{ Storage::url($category->image) }}" alt="" class="{{ theme('small_images', true) ? 'x3-category-image' : 'x3-category-image-large' }}">@endif
                        <x-x3.hardware :variant="\X3\Rackline\Theme::variant($category)" />
                    </div>
                    <h3>{{ $category->name }}</h3>
                    @if(theme('show_category_description',true))<div class="prose dark:prose-invert x3-description">{!! \Illuminate\Support\Str::sanitizeHtml($category->description ?? '') !!}</div>@endif
                    <a href="{{ route('category.show',['category'=>$category->slug]) }}" wire:navigate class="x3-card-link">{{ $x3['view_plans'] }} <span aria-hidden="true">↗</span></a>
                </article>
            @empty
                <div class="x3-empty"><x-ri-server-line class="size-8" /><h3>{{ $x3['empty'] }}</h3><p>{{ $x3['empty_text'] }}</p></div>
            @endforelse
        </div>
    </section>
    @if($studioFeatured->isNotEmpty())
        <section class="container x3-featured">
            <header class="x3-section-head"><div><p class="x3-studio-kicker">X3 / PLANS</p><h2>{{ $x3['featured'] }}</h2></div><p>{{ $x3['featured_note'] }}</p></header>
            <div class="x3-featured-grid">@foreach($studioFeatured as $product)<x-x3.plan-card :product="$product" />@endforeach</div>
        </section>
    @endif
    @if(theme('faq_enabled',true) && $studioFaqs)
        <section class="container x3-faq">
            <header class="x3-section-head"><div><p class="x3-studio-kicker">X3 / FAQ</p><h2>{{ $x3['faq_title'] }}</h2></div><p>{{ $x3['faq_note'] }}</p></header>
            <div class="x3-faq-list">@foreach($studioFaqs as $faq)<details><summary>{{ $faq['q'] }}<span aria-hidden="true">+</span></summary><p>{{ $faq['a'] }}</p></details>@endforeach</div>
        </section>
    @endif
    {!! hook('pages.home') !!}
</div>

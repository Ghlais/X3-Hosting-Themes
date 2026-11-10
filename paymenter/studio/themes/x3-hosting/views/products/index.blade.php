@php $x3 = require base_path('themes/x3-hosting/lang/' . (str_starts_with(app()->getLocale(), 'ar') ? 'ar' : 'en') . '/ui.php'); @endphp
@php
require_once base_path('themes/x3-hosting/lib/Theme.php');
$comparisonProducts = $products->filter(fn ($p) => $p->category !== null)->map(fn ($p) => \X3\Rackline\Theme::card($p))->values();
$comparisonLabels = ['missing'=>$x3['not_listed'], 'limit'=>$x3['compare_limit'], 'need'=>$x3['compare_need']];
@endphp
<div class="container x3-studio-route mt-14 x3-products" x-data="x3Compare(@js($comparisonProducts), @js($comparisonLabels))">
    <x-x3.studio-panel :title="$x3['plans']" variant="store" :description="$category->name" />
<div class="flex flex-col md:grid md:grid-cols-4 gap-4">
        <div class="flex flex-col gap-2">
            <div class="mx-auto container">
                <h2 class="text-2xl font-bold">{{ $category->name }}</h2>
                <article class="prose dark:prose-invert">
                    {!! $category->description !!}
                </article>
            </div>
            <div class="x3-plan-card flex flex-col bg-background-secondary border border-neutral p-4 rounded-lg">
                @foreach ($categories as $ccategory)
                <!-- List all categories simple under each other -->
                <a href="{{ route('category.show', ['category' => $ccategory->slug]) }}" wire:navigate
                    @if ($category->id == $ccategory->id) class="font-bold" @endif>
                    {{ $ccategory->name }}
                </a>
                @endforeach
            </div>
        </div>
        <div class="flex flex-col gap-6 col-span-3">
            @if (count($childCategories) >= 1)
            <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4 h-fit">
                @foreach ($childCategories as $childCategory)
                <div class="x3-plan-card flex flex-col bg-background-secondary border border-neutral p-4 rounded-lg">
                    @if(theme('small_images', false))
                    <div class="flex gap-x-3 items-center">
                        @endif
                        @if ($childCategory->image)
                        <img src="{{ Storage::url($childCategory->image) }}" alt="{{ $childCategory->name }}"
                            class="aspect-square rounded-md {{ theme('small_images', false) ? 'w-14 h-fit' : 'w-full object-cover object-center' }}">
                        @endif
                        <h2 class="text-xl font-bold">{{ $childCategory->name }}</h2>
                        @if(theme('small_images', false))
                    </div>
                    @endif
                    @if(theme('show_category_description', true))
                    <article class="mt-2 prose dark:prose-invert">
                        {!! $childCategory->description !!}
                    </article>
                    @endif
                    <a href="{{ route('category.show', ['category' => $childCategory->slug]) }}" wire:navigate class="mt-2">
                        <x-button.primary>
                            {{ __('common.button.view') }}
                        </x-button.primary>
                    </a>
                </div>
                @endforeach
            </div>
            @endif
            <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4 h-fit">
                @forelse ($products as $product)
                <x-x3.plan-card :product="$product" :compare="true" />
                @empty<div class="x3-empty col-span-full">{{ $x3['no_products'] }}</div>@endforelse
            </div>
        </div>
    </div>
    @if(theme('comparison_enabled', true))<x-x3.comparison />@endif
</div>

@props(['width' => null, 'content' => null, 'trigger' => null, 'showArrow' => true])
<div class="relative" x-data="{ open: false }" @keydown.escape.stop.prevent="open = false">
<button type="button" class="flex flex-row items-center px-2 py-1 text-sm font-semibold whitespace-nowrap text-base hover:text-base/80" @click="open = !open" :aria-expanded="open">
{{ $trigger }}
@if($showArrow)<x-ri-arrow-down-s-line x-bind:class="{ '-rotate-180': open }" class="md:block hidden size-4 text-base transition duration-150" />@endif
</button>
<div x-ref="dropdown" class="absolute end-0 mt-2 {{ $width ?? 'w-48' }} px-2 py-1 bg-background-secondary rounded-md shadow-lg z-10 border border-neutral" x-show="open" x-transition.opacity.duration.150ms @click.outside="open = false" x-cloak>{{ $content }}</div>
</div>

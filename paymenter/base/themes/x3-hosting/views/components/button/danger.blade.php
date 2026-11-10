<x-button.primary {{ $attributes->merge(['class' => 'bg-background-secondary border border-neutral bg-none text-base py-2 px-4 rounded hover:bg-background-secondary border border-neutral'])}}>
    {{ $slot }}
</x-button.primary>
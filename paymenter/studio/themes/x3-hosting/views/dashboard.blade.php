@php $x3 = require base_path('themes/x3-hosting/lang/' . (str_starts_with(app()->getLocale(), 'ar') ? 'ar' : 'en') . '/ui.php'); @endphp
<div class="container mt-14 x3-studio-route x3-studio-dashboard">
    <x-x3.studio-panel :title="$x3['dashboard_title']" variant="account" :description="__('dashboard.dashboard_description')" /><x-navigation.breadcrumb />
    

    @php
    $studioActive = auth()->user()->services()->where('status','active')->count();
    $studioUnpaid = auth()->user()->invoices()->where('status','pending')->count();
    $studioTickets = config('settings.tickets_disabled',false) ? null : auth()->user()->tickets()->where('status','!=','closed')->count();
@endphp
<div class="x3-studio-metrics">
    <a class="x3-studio-metric" href="{{ route('services') }}" wire:navigate><strong><bdi>{{ $studioActive }}</bdi></strong><span>{{ __('dashboard.active_services') }}</span></a>
    <a class="x3-studio-metric" href="{{ route('invoices') }}" wire:navigate><strong><bdi>{{ $studioUnpaid }}</bdi></strong><span>{{ __('dashboard.unpaid_invoices') }}</span></a>
    @if($studioTickets !== null)
    <a class="x3-studio-metric" href="{{ route('tickets') }}" wire:navigate><strong><bdi>{{ $studioTickets }}</bdi></strong><span>{{ __('dashboard.open_tickets') }}</span></a>
    @endif
</div>
    <x-x3.actions />
    <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mt-8 items-start">

        <div class="grid gap-8 items-start">
            <!-- Active Services -->
            <div class="x3-studio-block">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="bg-background-secondary border border-neutral p-2 rounded-lg">
                            <x-ri-archive-stack-fill class="size-5" />
                        </div>
                        <h2 class="text-xl font-semibold">{{ __('dashboard.active_services') }}</h2>
                    </div>
                    <span class="bg-primary flex items-center justify-center font-semibold rounded-md size-5 text-sm text-inverted">
                        {{ $studioActive }}
                    </span>
                </div>
                <div class="space-y-4">
                    <livewire:services.widget status="active" />
                </div>
                <x-navigation.link class="bg-background-secondary hover:bg-background-secondary/80 border border-neutral flex items-center justify-center rounded-lg"
                    :href="route('services')">
                    {{ __('dashboard.view_all') }}
                    <x-ri-arrow-right-fill class="size-5" />
                </x-navigation.link>
            </div>

            <!-- Open Tickets -->
            @if(!config('settings.tickets_disabled', false))
            <div class="x3-studio-block">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="bg-background-secondary border border-neutral p-2 rounded-lg">
                            <x-ri-customer-service-fill class="size-5" />

                        </div>
                        <h2 class="text-xl font-semibold">{{ __('dashboard.open_tickets') }}</h2>
                        <a href="{{ route('tickets.create') }}" wire:navigate>
                            <x-ri-add-fill class="size-5 h-5" />
                        </a>
                    </div>
                    <span class="bg-primary flex items-center justify-center font-semibold rounded-md size-5 text-sm text-inverted">
                        {{ $studioTickets }}
                    </span>
                </div>
                <div class="space-y-4">
                    <livewire:tickets.widget />
                </div>
                <x-navigation.link class="bg-background-secondary hover:bg-background-secondary/80 border border-neutral flex items-center justify-center rounded-lg"
                    :href="route('tickets')">
                    {{ __('dashboard.view_all') }}
                    <x-ri-arrow-right-fill class="size-5 h-5" />
                </x-navigation.link>
            </div>
            @endif
        </div>

        <div class="grid gap-8 items-start">
            <!-- Unpaid Invoices -->
            <div class="x3-studio-block">
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="bg-background-secondary border border-neutral p-2 rounded-lg">
                            <x-ri-receipt-fill class="size-5" />
                        </div>
                        <h2 class="text-xl font-semibold">{{ __('dashboard.unpaid_invoices') }}</h2>
                    </div>
                    <span class="bg-primary flex items-center justify-center font-semibold rounded-md size-5 text-sm text-inverted">
                        {{ $studioUnpaid }}
                    </span>
                </div>
                <div class="space-y-4">
                    <livewire:invoices.widget :limit="3" />
                </div>
                <x-navigation.link class="bg-background-secondary hover:bg-background-secondary/80 border border-neutral flex items-center justify-center rounded-lg"
                    :href="route('invoices')">
                    {{ __('dashboard.view_all') }}
                    <x-ri-arrow-right-fill class="size-5 h-5" />
                </x-navigation.link>
            </div>
            {!! hook('pages.dashboard') !!}
        </div>
    </div>
</div>

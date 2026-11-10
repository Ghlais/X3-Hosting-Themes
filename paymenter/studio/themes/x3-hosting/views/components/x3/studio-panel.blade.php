@props(['title', 'description' => null, 'variant' => 'account', 'kicker' => null])
@php
    $studioSections = ['account' => '01', 'service' => '02', 'billing' => '03', 'support' => '04', 'store' => '05'];
    $studioSection = $studioSections[$variant] ?? '01';
@endphp
<header {{ $attributes->class(['x3-studio-panel', 'x3-studio-panel-' . $variant]) }}>
    <div class="x3-studio-panel-copy">
        <p class="x3-studio-kicker"><span class="x3-studio-index" aria-hidden="true">{{ $studioSection }}</span> {{ $kicker ?? theme('brand_name', 'X3 Hosting') }}</p>
        <h1>{{ $title }}</h1>
        @if($description)<p class="x3-studio-panel-note">{{ $description }}</p>@endif
        @if(isset($actions))<div class="x3-studio-panel-actions">{{ $actions }}</div>@endif
    </div>
    <div class="x3-studio-panel-art" aria-hidden="true" dir="ltr">
        <div class="x3-studio-token">
            <div class="x3-studio-token-plate"></div><div class="x3-studio-token-plate"></div>
            <div class="x3-studio-token-face">
                @switch($variant)
                    @case('billing')<x-ri-receipt-line />@break
                    @case('support')<x-ri-ticket-line />@break
                    @case('service')<x-ri-server-line />@break
                    @case('store')<x-ri-archive-stack-fill />@break
                    @default<x-logo class="x3-studio-token-logo" />
                @endswitch
            </div>
        </div>
    </div>
</header>

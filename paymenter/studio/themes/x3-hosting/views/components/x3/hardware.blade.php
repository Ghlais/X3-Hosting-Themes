@props(['variant' => 'compute'])
@php $depthVariant=in_array($variant,['compute','game','apps'],true) ? $variant : 'compute'; @endphp
<div class="x3-hardware x3-hardware-{{ $depthVariant }} x3-depth-hardware" aria-hidden="true" data-x3-depth="hardware" dir="ltr">
    <div class="x3-depth-mini-shadow"></div>
    <div class="x3-depth-mini">
        <div class="x3-depth-mini-top"><span>X3 / {{ strtoupper($depthVariant) }}</span><i></i></div>
        <div class="x3-depth-mini-side"></div>
        <div class="x3-depth-mini-front"><span class="x3-depth-mini-number">X3</span>
            @if($depthVariant==='game')<div class="x3-depth-mini-fans"><i></i><i></i></div>
            @elseif($depthVariant==='apps')<div class="x3-depth-mini-drives">@foreach(range(1,5) as $depthDrive)<i></i>@endforeach</div>
            @else<div class="x3-depth-mini-grille"></div>@endif
            <span class="x3-depth-mini-led"></span>
        </div>
    </div>
</div>

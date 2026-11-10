@props(['variant' => 'compute'])
<svg class="x3-hardware x3-hardware-{{ $variant }}" viewBox="0 0 320 130" fill="none" aria-hidden="true" focusable="false">
<path d="M25 34 269 20l26 17-244 16Z" fill="currentColor" opacity=".07"/><path d="m25 34 26 19v65l-26-18Zm26 19 244-16v65L51 118Z" stroke="currentColor" opacity=".35"/>
<path d="M67 65 276 52M67 91l209-13" stroke="currentColor" opacity=".2"/>
@if($variant === 'game')
<circle cx="111" cy="81" r="19" stroke="currentColor" opacity=".7"/><circle cx="169" cy="77" r="19" stroke="currentColor" opacity=".7"/><path d="m99 81 24-2m-12-10v23m46-17 24-2m-12-10v23" stroke="currentColor"/>
@elseif($variant === 'apps')
@foreach([0,1,2,3,4] as $n)<path d="m{{ 76+$n*33 }} 69 22-2v29l-22 2Z" fill="currentColor" opacity="{{ .12+$n*.07 }}"/>@endforeach
@else
@foreach([0,1,2,3,4,5,6,7,8,9,10,11] as $n)<path d="m{{ 76+$n*14 }} 70v26" stroke="currentColor" opacity=".45"/>@endforeach
@endif
<path d="m259 89 10-1" stroke="currentColor" stroke-width="3"/><circle cx="281" cy="87" r="2" fill="currentColor"/>
</svg>

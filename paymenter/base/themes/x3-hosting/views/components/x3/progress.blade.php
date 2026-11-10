@props(['step' => 1])
@php require_once base_path('themes/x3-hosting/lib/Theme.php'); $t=\X3\Rackline\Theme::strings(); @endphp
<ol class="x3-purchase-progress" aria-label="{{ $t['purchase_note'] }}">
@foreach(['configure_step','review_step','payment_step'] as $key)
<li @if($loop->iteration === $step) aria-current="step" @endif class="{{ $loop->iteration < $step ? 'x3-step-done' : '' }}"><span aria-hidden="true">{{ str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }}</span>{{ $t[$key] }}</li>
@endforeach
</ol>

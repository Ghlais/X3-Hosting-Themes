@php
require_once base_path('themes/x3-hosting/lib/Theme.php'); $t=\X3\Rackline\Theme::strings();
$dueInvoices = Auth::user()->invoices()->where('status','pending')->with(['items','transactions','currency','snapshot','user'])->orderByRaw('due_at IS NULL')->orderBy('due_at')->orderBy('id')->limit(3)->get();
$renewals = Auth::user()->services()->with(['product','plan','currency'])->where('status','active')->whereHas('plan',fn($q)=>$q->where('type','recurring'))->whereNotNull('expires_at')->whereDate('expires_at','<=',now()->addDays(30))->orderBy('expires_at')->orderBy('id')->limit(3)->get();
@endphp
<section class="x3-next-actions"><header><p class="x3-eyebrow">X3 / NEXT</p><h2>{{ $t['attention'] }}</h2><p>{{ $t['attention_note'] }}</p></header>
<div class="x3-actions-grid"><div class="x3-action-panel"><h3>{{ $t['due_invoices'] }}</h3>
@forelse($dueInvoices as $invoice)
<article class="x3-action-row"><div><strong><bdi>#{{ $invoice->number ?: $invoice->id }}</bdi></strong><span>{{ $t['due_date'] }}: {{ \X3\Rackline\Theme::date($invoice->due_at) }}</span>@if($invoice->due_at && $invoice->due_at->lt(today()))<small>{{ $t['overdue'] }}</small>@endif</div><div><bdi class="x3-money">{{ $invoice->formattedRemaining }}</bdi><a href="{{ route('invoices.show',$invoice) }}" wire:navigate class="x3-text-link">{{ $t['pay_invoice'] }} ↗</a></div></article>
@empty<p class="x3-plan-note">{{ $t['all_clear'] }}</p>@endforelse
<a href="{{ route('invoices') }}" wire:navigate class="x3-text-link">{{ __('dashboard.view_all') }} ↗</a></div>
<div class="x3-action-panel"><h3>{{ $t['renewals'] }}</h3>
@forelse($renewals as $service)
<article class="x3-action-row"><div><strong>{{ $service->product?->name ?? '#'.$service->id }}</strong><span>{{ \X3\Rackline\Theme::date($service->expires_at) }}</span><small>{{ $service->expires_at->lt(today()) ? $t['overdue'] : $t['due_soon'] }}</small></div><div><bdi class="x3-money">{{ $service->formattedPrice }}</bdi><a href="{{ route('services.show',$service) }}" wire:navigate class="x3-text-link">{{ $t['manage_service'] }} ↗</a></div></article>
@empty<p class="x3-plan-note">{{ $t['no_renewals'] }}</p>@endforelse
<a href="{{ route('services') }}" wire:navigate class="x3-text-link">{{ $t['browse_services'] }} ↗</a></div></div>
@if(!config('settings.tickets_disabled',false))<a class="x3-text-link mt-4" href="{{ route('tickets.create') }}" wire:navigate>{{ $t['open_ticket'] }} ↗</a>@endif
</section>

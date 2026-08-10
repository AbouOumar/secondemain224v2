@extends('admin.layout')

@section('title', $payment->reference)

@section('content')
    <div class="admin-topbar">
        <div>
            <a href="{{ route('admin.payments.index') }}" class="text-muted small"><i class='bx bx-arrow-back'></i> Paiements</a>
            <h1 class="h4 mb-0">{{ $payment->reference }}</h1>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card p-3">
                <h2 class="h6 mb-3">Détails</h2>
                <dl class="row small mb-0">
                    <dt class="col-5">Utilisateur</dt><dd class="col-7">{{ $payment->user->name ?? '—' }}</dd>
                    <dt class="col-5">Montant</dt><dd class="col-7">{{ number_format($payment->montant, 0, ',', ' ') }} {{ $payment->currency->value ?? '' }}</dd>
                    <dt class="col-5">Méthode</dt><dd class="col-7">{{ str_replace('_', ' ', $payment->methode->value ?? '') }}</dd>
                    <dt class="col-5">Statut</dt><dd class="col-7">{{ $payment->status->value ?? '—' }}</dd>
                    <dt class="col-5">Référence externe</dt><dd class="col-7">{{ $payment->external_ref ?? '—' }}</dd>
                    <dt class="col-5">Payé le</dt><dd class="col-7">{{ $payment->paid_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                    <dt class="col-5">Créé le</dt><dd class="col-7">{{ $payment->created_at->format('d/m/Y H:i') }}</dd>
                </dl>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card p-3">
                <h2 class="h6 mb-3">Commande liée</h2>
                @if($payment->order)
                    <dl class="row small mb-0">
                        <dt class="col-5">Référence</dt><dd class="col-7">{{ $payment->order->reference }}</dd>
                        <dt class="col-5">Annonce</dt><dd class="col-7">{{ $payment->order->article->titre ?? '—' }}</dd>
                        <dt class="col-5">Acheteur</dt><dd class="col-7">{{ $payment->order->buyer->name ?? '—' }}</dd>
                        <dt class="col-5">Vendeur</dt><dd class="col-7">{{ $payment->order->seller->name ?? '—' }}</dd>
                        <dt class="col-5">Total</dt><dd class="col-7">{{ number_format($payment->order->total, 0, ',', ' ') }} GNF</dd>
                        <dt class="col-5">Statut commande</dt><dd class="col-7">{{ $payment->order->status->value ?? '—' }}</dd>
                    </dl>
                @else
                    <p class="text-muted small mb-0">Aucune commande associée.</p>
                @endif
            </div>
        </div>

        @if($payment->external_data)
            <div class="col-12">
                <div class="card p-3">
                    <h2 class="h6 mb-3">Données de la passerelle</h2>
                    <pre class="small bg-light p-3 rounded mb-0" style="white-space: pre-wrap;">{{ json_encode($payment->external_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        @endif
    </div>
@endsection

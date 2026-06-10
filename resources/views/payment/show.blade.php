@extends('layouts.app')

@section('content')
<div class="container py-4">
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h4 class="mb-0">Récapitulatif de la commande</h4>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex gap-3 mb-4">
                        @if($order->article->images->count() > 0)
                            <img src="{{ $order->article->images->first()->url }}?fit=fill&w=120&h=120" class="rounded" style="width:100px;height:100px;object-fit:cover;">
                        @endif
                        <div>
                            <h5 class="fw-bold mb-1">{{ $order->article->titre }}</h5>
                            <p class="text-muted small mb-2">Réf: {{ $order->reference }}</p>
                            <p class="mb-1"><strong>Prix article :</strong> {{ number_format($order->prix_article, 0, ',', ' ') }} GNF</p>
                            @if($order->with_delivery)
                                <p class="mb-1"><strong>Livraison :</strong> {{ number_format($order->delivery_prix, 0, ',', ' ') }} GNF</p>
                            @endif
                            <hr>
                            <p class="fs-5 fw-bold mb-0" style="color:var(--primary);">
                                Total : {{ number_format($order->total, 0, ',', ' ') }} GNF
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h4 class="mb-0">Moyen de paiement</h4>
                </div>
                <div class="card-body p-4">
                    <p class="text-muted mb-4">Sélectionnez votre moyen de paiement et entrez votre numéro de téléphone.</p>

                    <form method="POST" action="{{ route('payment.process.djomy', $order) }}" id="paymentForm">
                        @csrf

                        <div class="mb-4">
                            <label class="form-label fw-medium">Numéro de téléphone *</label>
                            <input type="tel" name="phone" class="form-control form-control-lg"
                                   value="{{ old('phone', auth()->user()->phone) }}"
                                   placeholder="Ex: 622 30 00 01" required>
                            <small class="text-muted">Vous recevrez une notification sur ce numéro pour confirmer le paiement.</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-medium">Méthode de paiement</label>
                            <div class="row g-3" id="paymentMethods">
                                <div class="col-md-4">
                                    <div class="card border-2 payment-method selected" data-method="djomy_om" onclick="selectMethod(this, 'djomy_om')">
                                        <div class="card-body text-center p-3">
                                            <div class="mb-2" style="font-size:2rem;">📱</div>
                                            <h6 class="mb-1">Orange Money</h6>
                                            <small class="text-muted">Via Djomy</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-2 payment-method" data-method="djomy_momo" onclick="selectMethod(this, 'djomy_momo')">
                                        <div class="card-body text-center p-3">
                                            <div class="mb-2" style="font-size:2rem;">📱</div>
                                            <h6 class="mb-1">MTN MoMo</h6>
                                            <small class="text-muted">Via Djomy</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-2 payment-method" data-method="djomy_card" onclick="selectMethod(this, 'djomy_card')">
                                        <div class="card-body text-center p-3">
                                            <div class="mb-2" style="font-size:2rem;">💳</div>
                                            <h6 class="mb-1">Carte bancaire</h6>
                                            <small class="text-muted">Visa, MasterCard</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class='bx bx-lock'></i> Payer {{ number_format($order->total, 0, ',', ' ') }} GNF
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="{{ route('articles.show', $order->article->slug) }}" class="text-muted small">Annuler et retourner à l'annonce</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.payment-method { cursor: pointer; transition: all 0.2s; }
.payment-method:hover { border-color: var(--primary) !important; transform: translateY(-2px); }
.payment-method.selected { border-color: var(--primary) !important; background: #f0fdf4; }
.payment-method.selected .card-body { color: var(--primary); }
</style>
@endpush

@push('scripts')
<script>
function selectMethod(el, method) {
    document.querySelectorAll('.payment-method').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
}
</script>
@endpush

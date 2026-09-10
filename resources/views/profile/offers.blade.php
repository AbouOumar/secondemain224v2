@extends('layouts.app')
@section('content')
<div class="container py-4">
    @include('profile.nav')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Mes offres</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($threads->isEmpty())
        <div class="text-center py-5">
            <i class='bx bx-purchase-tag' style="font-size:4rem;color:var(--gray-400);"></i>
            <p class="text-muted mt-3">Aucune négociation pour le moment.</p>
            <a href="{{ url('/') }}" class="btn btn-primary">Parcourir les annonces</a>
        </div>
    @else
        @foreach($threads as $thread)
            @php
                $last = $thread->last();
                $first = $thread->first();
                $article = $first->article;
                $isBuyer = $first->buyer_id === auth()->id();
                $otherParty = $isBuyer ? $first->seller : $first->buyer;
                $isRecipient = $last->status->value === 'en_attente' && $last->recipient()->id === auth()->id();
                $isProposer = $last->status->value === 'en_attente' && $last->proposer()->id === auth()->id();
            @endphp
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                        <div class="d-flex gap-3 align-items-center">
                            @if($article)
                                <img src="{{ $article->images->first()->url ?? 'https://placehold.co/80x80/e2e8f0/94a3b8?text=?' }}?fit=fill&w=80&h=80" style="width:64px;height:64px;object-fit:cover;border-radius:8px;">
                                <div>
                                    <a href="{{ route('articles.show', $article->slug) }}" class="fw-bold text-dark text-decoration-none">{{ $article->titre }}</a>
                                    <p class="text-muted small mb-0">Prix affiché : {{ number_format($article->prix, 0, ',', ' ') }} GNF · {{ $isBuyer ? 'Vendeur' : 'Acheteur' }} : {{ $otherParty->name }}</p>
                                </div>
                            @else
                                <span class="text-muted">Annonce supprimée</span>
                            @endif
                        </div>
                        <span class="badge {{ match($last->status->value) {
                            'acceptee' => 'bg-success',
                            'refusee', 'expiree', 'annulee' => 'bg-secondary',
                            default => 'bg-warning text-dark',
                        } }}">{{ $last->status->value }}</span>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-3 small">
                        @foreach($thread as $round)
                            <span class="badge badge-soft-secondary bg-light text-dark border">
                                {{ $round->made_by === 'acheteur' ? 'Acheteur' : 'Vendeur' }} : {{ number_format($round->montant, 0, ',', ' ') }} GNF
                            </span>
                        @endforeach
                    </div>

                    @if($isRecipient)
                        <div class="d-flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('offers.accept', $last) }}">
                                @csrf
                                <button class="btn btn-sm btn-success"><i class="bx bx-check"></i> Accepter {{ number_format($last->montant, 0, ',', ' ') }} GNF</button>
                            </form>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#counter-{{ $last->id }}"><i class="bx bx-transfer"></i> Contre-offre</button>
                            <form method="POST" action="{{ route('offers.reject', $last) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-danger"><i class="bx bx-x"></i> Refuser</button>
                            </form>
                        </div>

                        <div class="modal fade" id="counter-{{ $last->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <form method="POST" action="{{ route('offers.counter', $last) }}" class="modal-content">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">Faire une contre-offre</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <label class="form-label small">Votre montant (GNF)</label>
                                        <input type="number" name="montant" class="form-control" min="1" required>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" class="btn btn-primary">Envoyer</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @elseif($isProposer)
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted small">En attente de la réponse de {{ $last->recipient()->name }}...</span>
                            <form method="POST" action="{{ route('offers.cancel', $last) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary">Annuler mon offre</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection

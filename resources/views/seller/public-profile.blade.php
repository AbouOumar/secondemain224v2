@extends('layouts.app')

@section('content')
<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="alert alert-info">{{ session('info') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card border-0 shadow-sm mb-4" style="border-radius: 18px;">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <img src="{{ $user->avatar ? asset('storage/'.$user->avatar) : 'https://placehold.co/96x96/e2e8f0/94a3b8?text='.substr($user->name ?? '?', 0, 1) }}"
                     class="rounded-circle" style="width:80px;height:80px;object-fit:cover;">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h3 class="fw-bold mb-0">{{ $user->name }}</h3>
                        @include('partials.verified-badge', ['user' => $user])
                    </div>
                    <p class="text-muted small mb-0">
                        <i class='bx bx-calendar'></i> Membre depuis {{ $stats['member_since']->format('M Y') }}
                    </p>
                </div>
                <div class="d-flex gap-2">
                    @auth
                        @if(auth()->id() !== $user->id)
                            <a href="{{ route('messages.show', ['user' => $user->id]) }}" class="btn btn-outline-primary"><i class="bx bx-message-dots"></i> Contacter</a>
                            <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#report-user-{{ $user->id }}"><i class="bx bx-flag"></i> Signaler</button>
                        @endif
                    @endauth
                </div>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-6 col-md-3">
                    <div class="border rounded-3 p-3 text-center">
                        <div class="fs-4 fw-bold" style="color:var(--primary);">{{ $stats['sales_count'] }}</div>
                        <div class="small text-muted">Ventes conclues</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded-3 p-3 text-center">
                        <div class="fs-4 fw-bold" style="color:var(--primary);">{{ $stats['rating_avg'] ?: '—' }}<span class="fs-6 text-muted">/5</span></div>
                        <div class="small text-muted">{{ $stats['rating_count'] }} avis</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded-3 p-3 text-center">
                        <div class="fs-4 fw-bold" style="color:var(--primary);">{{ $stats['response_rate'] }}%</div>
                        <div class="small text-muted">Taux de réponse</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded-3 p-3 text-center">
                        <div class="fs-4 fw-bold" style="color:var(--primary);">{{ $articles->total() }}</div>
                        <div class="small text-muted">Annonces actives</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h5 class="fw-bold mb-3">Annonces de {{ $user->name }}</h5>
    <div class="row g-3">
        @forelse($articles as $item)
            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                <a href="{{ route('articles.show', $item->slug) }}" class="text-decoration-none text-dark">
                    <div class="card h-100 border-0 shadow-sm">
                        <img src="{{ $item->images->first()->url ?? 'https://placehold.co/300x200/e2e8f0/94a3b8?text=Photo' }}?fit=fill&w=300&h=200"
                             class="card-img-top" style="height:160px;object-fit:cover;" loading="lazy">
                        <div class="card-body p-3">
                            <h6 class="text-truncate mb-1">{{ $item->titre }}</h6>
                            <span class="fw-bold" style="color:var(--primary);">{{ number_format($item->prix, 0, ',', ' ') }} {{ $item->currency->value }}</span>
                        </div>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12"><p class="text-muted">Aucune annonce active pour le moment.</p></div>
        @endforelse
    </div>
    <div class="mt-3">{{ $articles->links() }}</div>
</div>

@auth
    @if(auth()->id() !== $user->id)
        @include('partials.report-modal', ['type' => 'user', 'id' => $user->id, 'label' => $user->name])
    @endif
@endauth
@endsection

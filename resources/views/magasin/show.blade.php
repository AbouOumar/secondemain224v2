@extends('layouts.app')

@section('content')
<!-- Bannière -->
<section style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); height: 250px; position: relative; overflow: hidden;">
    @if($magasin->couverture)
        <img src="{{ $magasin->cover_url }}" alt="{{ $magasin->nom_magasin }}" style="width:100%;height:100%;object-fit:cover;opacity:0.3;">
    @endif
    <div style="position:absolute;bottom:0;left:0;right:0;padding:2rem;display:flex;align-items:flex-end;gap:1.5rem;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
            @if($magasin->logo)
                <img src="{{ $magasin->logo_url }}" alt="Logo" style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid #fff;">
            @else
                <div style="width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.2);display:flex;align-items:center;justify-content:center;border:3px solid #fff;">
                    <i class='bx bx-store' style="font-size:2.5rem;color:#fff;"></i>
                </div>
            @endif
            <div>
                <h1 class="text-white mb-1" style="text-shadow:0 2px 4px rgba(0,0,0,0.3);">{{ $magasin->nom_magasin }}</h1>
                @if($magasin->is_verified)
                    <span class="badge bg-success"><i class='bx bx-check-circle'></i> Magasin vérifié</span>
                @endif
                @if($magasin->adresse)
                    <span class="badge bg-light text-dark ms-2"><i class='bx bx-map'></i> {{ $magasin->adresse }}</span>
                @endif
            </div>
        </div>
    </div>
</section>

<div class="container py-4">
    <div class="row g-4">
        <!-- Sidebar infos -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    @if($magasin->description)
                        <p class="text-muted">{{ $magasin->description }}</p>
                        <hr>
                    @endif

                    @if($magasin->telephone)
                        <p class="mb-2"><i class='bx bx-phone'></i> <a href="tel:{{ $magasin->telephone }}">{{ $magasin->telephone }}</a></p>
                    @endif

                    @if($magasin->adresse)
                        <p class="mb-2"><i class='bx bx-map'></i> {{ $magasin->adresse }}</p>
                    @endif

                    @if($magasin->horaire)
                        <hr>
                        <h6 class="fw-bold mb-2">Horaires</h6>
                        @foreach($magasin->horaire as $h)
                            <div class="d-flex justify-content-between small mb-1">
                                <span>{{ is_array($h) ? ($h['jour'] ?? '') : '' }}</span>
                                <span class="text-muted">{{ is_array($h) ? (($h['ouverture'] ?? '') . ' - ' . ($h['fermeture'] ?? '')) : '' }}</span>
                            </div>
                        @endforeach
                    @endif

                    <hr>
                    <p class="small text-muted mb-0">
                        <i class='bx bx-package'></i> {{ $articles->total() }} article(s)
                    </p>
                </div>
            </div>
        </div>

        <!-- Grille d'articles -->
        <div class="col-lg-9">
            <h4 class="fw-bold mb-4">Nos articles</h4>

            @if($articles->count() > 0)
                <div class="row g-4">
                    @foreach($articles as $item)
                        <div class="col-6 col-md-4 article-item">
                            <div class="card article-card h-100 border-0 shadow-sm">
                                <div class="position-relative">
                                    <a href="{{ route('articles.show', $item->slug) }}">
                                        <img src="{{ $item->images->first()->url ?? 'https://placehold.co/300x200/e2e8f0/94a3b8?text=Photo' }}" alt="{{ $item->titre }}" class="card-img-top" loading="lazy" style="height:180px;object-fit:cover;">
                                    </a>
                                    <span class="badge price-badge">{{ number_format($item->prix, 0, ',', ' ') }} {{ $item->currency->value }}</span>
                                    @if($item->is_boosted)
                                        <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-2"><i class='bx bx-rocket'></i></span>
                                    @endif
                                </div>
                                <div class="card-body d-flex flex-column p-3">
                                    <h6 class="mb-1 text-truncate">{{ $item->titre }}</h6>
                                    <p class="text-muted small mb-2 flex-grow-1">{{ Str::limit($item->description, 80) }}</p>
                                    @if($item->stock > 0)
                                        <span class="badge bg-success text-white mb-2 align-self-start"><i class='bx bx-box'></i> En stock ({{ $item->stock }})</span>
                                    @else
                                        <span class="badge bg-danger text-white mb-2 align-self-start"><i class='bx bxs-box'></i> Rupture</span>
                                    @endif
                                    <a href="{{ route('articles.show', $item->slug) }}" class="btn btn-sm btn-primary w-100">Voir détails</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($articles->hasPages())
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $articles->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class='bx bx-package fs-1 text-muted mb-3'></i>
                    <p class="text-muted">Ce magasin n'a pas encore d'articles en ligne.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

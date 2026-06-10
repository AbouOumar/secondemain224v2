@extends('layouts.app')

@section('content')
<div class="container py-4">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <header class="mb-4 d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h1 class="h3 mb-1">Mon Magasin</h1>
            <p class="text-muted mb-0">{{ $magasin->nom_magasin }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ $magasin->url }}" class="btn btn-outline-primary" target="_blank">
                <i class='bx bx-show'></i> Voir la boutique
            </a>
            <a href="{{ route('magasin.edit') }}" class="btn btn-primary">
                <i class='bx bx-edit'></i> Modifier
            </a>
        </div>
    </header>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    @if($magasin->logo)
                        <img src="{{ $magasin->logo_url }}" alt="Logo" style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:1rem;">
                    @else
                        <div style="width:80px;height:80px;border-radius:50%;background:#e2e8f0;margin:0 auto 1rem;display:flex;align-items:center;justify-content:center;">
                            <i class='bx bx-store' style="font-size:2rem;color:#94a3b8;"></i>
                        </div>
                    @endif
                    <h5>{{ $magasin->nom_magasin }}</h5>
                    <span class="badge bg-{{ $magasin->is_verified ? 'success' : 'secondary' }}">
                        {{ $magasin->is_verified ? 'Vérifié' : 'Non vérifié' }}
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <i class='bx bx-package fs-1 text-primary mb-2'></i>
                    <div class="fs-3 fw-bold">{{ $articles->total() }}</div>
                    <small class="text-muted">Articles dans le magasin</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex flex-column justify-content-center">
                    <h6 class="fw-bold mb-2">Liens rapides</h6>
                    <a href="{{ route('articles.create') }}" class="btn btn-sm btn-outline-primary mb-2">
                        <i class='bx bx-plus'></i> Nouvel article
                    </a>
                    <a href="{{ route('profile.listings') }}" class="btn btn-sm btn-outline-secondary">
                        <i class='bx bx-list-ul'></i> Gérer les articles
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">Mes articles</h5>
        </div>
        <div class="card-body p-3">
            @if($articles->count() > 0)
                <div class="row g-3">
                    @foreach($articles as $article)
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <img src="{{ $article->images->first()->url ?? 'https://placehold.co/300x200/e2e8f0/94a3b8?text=Photo' }}" class="card-img-top" alt="{{ $article->titre }}" style="height:140px;object-fit:cover;">
                                <div class="card-body p-2">
                                    <h6 class="mb-1 text-truncate" style="font-size:0.9rem;">{{ $article->titre }}</h6>
                                    <p class="text-muted small mb-1">{{ number_format($article->prix, 0, ',', ' ') }} {{ $article->currency->value }}</p>
                                    <a href="{{ route('articles.show', $article->slug) }}" class="btn btn-sm btn-outline-primary w-100">Voir</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if($articles->hasPages())
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $articles->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class='bx bx-package fs-1 text-muted mb-3'></i>
                    <p class="text-muted">Vous n'avez pas encore d'articles dans votre magasin.</p>
                    <a href="{{ route('articles.create') }}" class="btn btn-primary">Publier un article</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

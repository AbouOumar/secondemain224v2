@extends('admin.layout')

@section('title', $user->name)

@section('content')
    <div class="admin-topbar">
        <div>
            <a href="{{ route('admin.users.index') }}" class="text-muted small"><i class='bx bx-arrow-back'></i> Utilisateurs</a>
            <h1 class="h4 mb-0">{{ $user->name }}</h1>
        </div>
        <div class="d-flex gap-2">
            @if($user->status?->value === 'suspendu')
                <form method="POST" action="{{ route('admin.users.activate', $user) }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-success">Réactiver</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.users.suspend', $user) }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-warning" onclick="return confirm('Suspendre ce compte ?')">Suspendre</button>
                </form>
            @endif
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}">
                @csrf
                @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer définitivement ce compte ?')">Supprimer</button>
            </form>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card p-3">
                <h2 class="h6 mb-3">Informations</h2>
                <dl class="row small mb-0">
                    <dt class="col-5">Email</dt><dd class="col-7">{{ $user->email ?? '—' }}</dd>
                    <dt class="col-5">Téléphone</dt><dd class="col-7">{{ $user->phone }}</dd>
                    <dt class="col-5">Rôle</dt><dd class="col-7">{{ str_replace('_', ' ', $user->role?->value ?? '—') }}</dd>
                    <dt class="col-5">Statut</dt><dd class="col-7">{{ $user->status?->value ?? '—' }}</dd>
                    <dt class="col-5">Vérifié</dt><dd class="col-7">{{ $user->is_verified ? 'Oui' : 'Non' }}</dd>
                    <dt class="col-5">Inscrit le</dt><dd class="col-7">{{ $user->created_at->format('d/m/Y H:i') }}</dd>
                    @if($user->partner)
                        <dt class="col-5">Magasin</dt>
                        <dd class="col-7"><a href="{{ route('admin.partners.index') }}">{{ $user->partner->nom_magasin }}</a></dd>
                    @endif
                </dl>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card p-3 mb-3">
                <h2 class="h6 mb-3">Dernières annonces ({{ $user->articles->count() }})</h2>
                @forelse($user->articles as $article)
                    <div class="d-flex justify-content-between border-bottom py-2 small">
                        <span>{{ $article->titre }}</span>
                        <span class="text-muted">{{ number_format($article->prix, 0, ',', ' ') }} {{ $article->currency->value ?? '' }}</span>
                    </div>
                @empty
                    <p class="text-muted small mb-0">Aucune annonce.</p>
                @endforelse
            </div>

            <div class="card p-3">
                <h2 class="h6 mb-3">Commandes</h2>
                <p class="small text-muted mb-1">En tant qu'acheteur : {{ $user->ordersAsBuyer->count() }}</p>
                <p class="small text-muted mb-0">En tant que vendeur : {{ $user->ordersAsSeller->count() }}</p>
            </div>
        </div>
    </div>
@endsection

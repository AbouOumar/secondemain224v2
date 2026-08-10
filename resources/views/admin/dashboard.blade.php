@extends('admin.layout')

@section('title', 'Tableau de bord')

@section('content')
    <div class="admin-topbar">
        <h1 class="h4 mb-0">Tableau de bord</h1>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-card">
                <div class="value">{{ number_format($stats['total_users']) }}</div>
                <div class="label">Utilisateurs</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-card">
                <div class="value">{{ number_format($stats['total_articles']) }}</div>
                <div class="label">Annonces</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-card">
                <div class="value">{{ number_format($stats['total_orders']) }}</div>
                <div class="label">Commandes</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-card">
                <div class="value">{{ number_format($stats['total_revenue'], 0, ',', ' ') }}</div>
                <div class="label">Revenus (GNF)</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-card">
                <div class="value text-warning">{{ number_format($stats['pending_moderation']) }}</div>
                <div class="label">Annonces à modérer</div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="stat-card">
                <div class="value text-warning">{{ number_format($stats['pending_partners']) }}</div>
                <div class="label">Magasins à vérifier</div>
            </div>
        </div>
    </div>

    @if($stats['pending_moderation'] > 0 || $stats['pending_partners'] > 0)
        <div class="alert alert-warning d-flex flex-wrap gap-2 align-items-center mb-4">
            <i class='bx bx-error'></i>
            <span>
                @if($stats['pending_moderation'] > 0)
                    {{ $stats['pending_moderation'] }} annonce(s) en attente de modération.
                    <a href="{{ route('admin.articles.index') }}" class="alert-link">Voir</a>
                @endif
                @if($stats['pending_partners'] > 0)
                    &middot; {{ $stats['pending_partners'] }} magasin(s) en attente de vérification.
                    <a href="{{ route('admin.partners.index') }}" class="alert-link">Voir</a>
                @endif
            </span>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card p-3">
                <h2 class="h6 mb-3">Utilisateurs par rôle</h2>
                @forelse($usersByRole as $role => $count)
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-capitalize">{{ str_replace('_', ' ', $role) }}</span>
                        <strong>{{ $count }}</strong>
                    </div>
                @empty
                    <p class="text-muted small mb-0">Aucune donnée.</p>
                @endforelse
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card p-3">
                <h2 class="h6 mb-3">Annonces par catégorie</h2>
                @forelse($articlesByCategory as $row)
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span>{{ $row->category->libelle ?? 'Sans catégorie' }}</span>
                        <strong>{{ $row->count }}</strong>
                    </div>
                @empty
                    <p class="text-muted small mb-0">Aucune donnée.</p>
                @endforelse
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card p-3">
                <h2 class="h6 mb-3">Derniers inscrits</h2>
                @forelse($recentUsers as $user)
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a>
                        <span class="text-muted small">{{ $user->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-muted small mb-0">Aucune donnée.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="card p-3 mt-3">
        <h2 class="h6 mb-3">Dernières commandes</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Annonce</th>
                        <th>Acheteur</th>
                        <th>Vendeur</th>
                        <th>Total</th>
                        <th>Statut</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                        <tr>
                            <td>{{ $order->reference }}</td>
                            <td>{{ $order->article->titre ?? '—' }}</td>
                            <td>{{ $order->buyer->name ?? '—' }}</td>
                            <td>{{ $order->seller->name ?? '—' }}</td>
                            <td>{{ number_format($order->total, 0, ',', ' ') }} GNF</td>
                            <td><span class="badge badge-soft-secondary">{{ $order->status->value }}</span></td>
                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Aucune commande.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('content')
<div class="container py-4">
    <header class="mb-4">
        <h1 class="h3 mb-0">Tableau de bord Revendeur Pro</h1>
        <p class="text-muted">Gérez votre activité professionnelle avec des outils avancés</p>
    </header>

    @if (auth()->user()->is_verified)
        <div class="alert alert-success mb-4">
            <i class='bx bx-check-circle me-2'></i>
            Vous êtes un revendeur vérifié ! Votre badge est affiché sur toutes vos annonces.
        </div>
    @else
        <div class="alert alert-warning mb-4">
            <i class='bx bx-info-circle me-2'></i>
            Pour accéder à toutes les fonctionnalités Pro, devenez un revendeur vérifié.
            <a href="{{ route('seller.pro.verification') }}" class="alert-link">Commencer la vérification</a>
        </div>
    @endif

    <div class="row g-4">
        <!-- Magasin -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="card-title mb-0">Mon Magasin</h5>
                        <i class='bx bx-store fs-4 text-primary'></i>
                    </div>
                    @php $partner = auth()->user()->partner; @endphp
                    @if($partner)
                        <p class="mb-1 fw-medium">{{ $partner->nom_magasin }}</p>
                        <p class="text-muted small mb-2">{{ $partner->articles()->count() }} article(s)</p>
                        <div class="d-flex gap-2">
                            <a href="{{ route('magasin.dashboard') }}" class="btn btn-sm btn-outline-primary">Gérer</a>
                            <a href="{{ $partner->url }}" class="btn btn-sm btn-outline-secondary" target="_blank">Voir</a>
                        </div>
                    @else
                        <p class="text-muted small mb-2">Vous n'avez pas encore de magasin.</p>
                        <a href="{{ route('magasin.setup') }}" class="btn btn-sm btn-primary">Créer mon magasin</a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Subscription Status -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Abonnement</h5>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>{{ ucfirst(Auth::user()->subscription?->plan_type ?? 'basique') }}</span>
                        <span class="badge bg-{{ Auth::user()->subscription?->isActive() ?? false ? 'success' : 'secondary' }}">
                            {{ Auth::user()->subscription?->isActive() ?? false ? 'Actif' : 'Inactif' }}
                        </span>
                    </div>
                    @if (Auth::user()->subscription)
                        <p class="card-text">
                            <small class="text-muted">
                                Début : {{ Auth::user()->subscription->starts_at->format('d/m/Y') }}<br>
                                Fin : {{ Auth::user()->subscription->ends_at->format('d/m/Y') }}
                            </small>
                        </p>
                        <a href="{{ route('seller.pro.subscription') }}" class="btn btn-sm btn-outline-primary">Gérer l'abonnement</a>
                    @else
                        <div class="text-center py-3">
                            <a href="{{ route('seller.pro.subscription') }}" class="btn btn-primary w-75">Souscrire</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Verification Status -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Vérification</h5>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Statut</span>
                        <span class="badge bg-{{ auth()->user()->is_verified ? 'success' : 'warning' }}">
                            {{ auth()->user()->is_verified ? 'Vérifié' : 'En attente' }}
                        </span>
                    </div>
                    @if (!auth()->user()->is_verified)
                        <p class="card-text text-muted small">
                            Soumettez vos documents pour obtenir le badge vérifié qui augmente la confiance des acheteurs.
                        </p>
                        <a href="{{ route('seller.pro.verification') }}" class="btn btn-sm btn-outline-primary">Soumettre les documents</a>
                    @else
                        <p class="card-text">
                            <small class="text-muted">
                                Vérifié le : {{ auth()->user()->verified_at->format('d/m/Y') }}
                            </small>
                        </p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Statistiques rapides</h5>
                    <div class="row g-3 text-center">
                        <div class="col-4">
                            <div class="d-flex flex-column align-items-center">
                                <i class='bx bx-box fs-3 text-primary'></i>
                                <div class="fs-5 fw-bold">{{ $total ?? 0 }}</div>
                                <small class="text-muted">Total articles</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="d-flex flex-column align-items-center">
                                <i class='bx bx-store fs-3 text-success'></i>
                                <div class="fs-5 fw-bold">{{ $enVente ?? 0 }}</div>
                                <small class="text-muted">En vente</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="d-flex flex-column align-items-center">
                                <i class='bx bx-check-circle fs-3 text-danger'></i>
                                <div class="fs-5 fw-bold">{{ $vendus ?? 0 }}</div>
                                <small class="text-muted">Vendus</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(isset($earnings))
    <div class="row g-3 mt-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center p-3" style="border-radius: 18px;">
                <h6 class="text-muted mb-1">Aujourd'hui</h6>
                <h4 class="fw-bold mb-0" style="color:var(--primary);">{{ number_format($earnings['aujourd_hui'], 0, ',', ' ') }} GNF</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center p-3" style="border-radius: 18px;">
                <h6 class="text-muted mb-1">Cette semaine</h6>
                <h4 class="fw-bold mb-0" style="color:var(--primary);">{{ number_format($earnings['cette_semaine'], 0, ',', ' ') }} GNF</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center p-3" style="border-radius: 18px;">
                <h6 class="text-muted mb-1">Ce mois</h6>
                <h4 class="fw-bold mb-0" style="color:var(--primary);">{{ number_format($earnings['ce_mois'], 0, ',', ' ') }} GNF</h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm text-center p-3" style="border-radius: 18px;">
                <h6 class="text-muted mb-1">Cette année</h6>
                <h4 class="fw-bold mb-0" style="color:var(--primary);">{{ number_format($earnings['cette_annee'], 0, ',', ' ') }} GNF</h4>
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Activity -->
    @if(isset($recentOrders) && $recentOrders->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="fw-bold mb-0">Commandes récentes</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($recentOrders as $order)
                        <div class="list-group-item border-0 py-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="fw-bold">{{ $order->article->titre ?? 'Article' }}</h6>
                                    <p class="mb-1 text-muted small">{{ $order->reference }} - {{ number_format($order->total, 0, ',', ' ') }} GNF</p>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-{{ $order->status === 'livre' ? 'success' : ($order->status === 'paye' ? 'warning' : 'secondary') }}">{{ $order->status }}</span>
                                    @if($order->delivery && in_array($order->delivery->status->value, ['acceptee', 'en_cours', 'effectuee']))
                                        <a href="{{ route('deliveries.tracking', $order->delivery->id) }}" class="btn btn-sm btn-outline-info mt-1">
                                            <i class='bx bx-map'></i> Suivre
                                        </a>
                                    @endif
                                </div>
                            </div>
                            <hr class="my-1">
                            <small class="text-muted">{{ $order->created_at->diffForHumans() }}</small>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

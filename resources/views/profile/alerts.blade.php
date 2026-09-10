@extends('layouts.app')
@section('content')
<div class="container py-4">
    @include('profile.nav')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Mes alertes de recherche</h2>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($alerts->isEmpty())
        <div class="text-center py-5">
            <i class='bx bx-bell' style="font-size:4rem;color:var(--gray-400);"></i>
            <p class="text-muted mt-3">Aucune alerte pour le moment.</p>
            <p class="text-muted small">Depuis la page d'accueil, appliquez des filtres de recherche puis cliquez sur « Créer une alerte » pour être prévenu des nouvelles annonces correspondantes.</p>
            <a href="{{ url('/') }}" class="btn btn-primary">Parcourir les annonces</a>
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Critères</th>
                            <th>Statut</th>
                            <th>Créée le</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alerts as $alert)
                            <tr>
                                <td>{{ $alert->label() }}</td>
                                <td>
                                    <span class="badge {{ $alert->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $alert->is_active ? 'Active' : 'Désactivée' }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $alert->created_at->format('d/m/Y') }}</td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <form method="POST" action="{{ route('alerts.toggle', $alert) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-secondary">{{ $alert->is_active ? 'Désactiver' : 'Activer' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('alerts.destroy', $alert) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cette alerte ?')"><i class="bx bx-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

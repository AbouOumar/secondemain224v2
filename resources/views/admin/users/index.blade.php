@extends('admin.layout')

@section('title', 'Utilisateurs')

@section('content')
    <div class="admin-topbar">
        <h1 class="h4 mb-0">Utilisateurs</h1>
    </div>

    <form method="GET" class="card p-3 mb-3 d-flex flex-row flex-wrap gap-2 align-items-end">
        <div>
            <label class="form-label small mb-1">Recherche</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Nom, email, téléphone">
        </div>
        <div>
            <label class="form-label small mb-1">Rôle</label>
            <select name="role" class="form-select form-select-sm">
                <option value="">Tous</option>
                @foreach(['acheteur', 'vendeur', 'revendeur_pro', 'motard', 'admin'] as $role)
                    <option value="{{ $role }}" {{ request('role') === $role ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $role)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label small mb-1">Statut</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Tous</option>
                <option value="actif" {{ request('status') === 'actif' ? 'selected' : '' }}>Actif</option>
                <option value="suspendu" {{ request('status') === 'suspendu' ? 'selected' : '' }}>Suspendu</option>
                <option value="en_attente" {{ request('status') === 'en_attente' ? 'selected' : '' }}>En attente</option>
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">Réinitialiser</a>
        </div>
    </form>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Contact</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Inscrit le</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td><a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a></td>
                            <td class="small text-muted">{{ $user->email ?? $user->phone }}</td>
                            <td><span class="badge badge-soft-secondary">{{ str_replace('_', ' ', $user->role?->value ?? '—') }}</span></td>
                            <td>
                                @php $status = $user->status?->value; @endphp
                                <span class="badge {{ $status === 'actif' ? 'badge-soft-success' : ($status === 'suspendu' ? 'badge-soft-danger' : 'badge-soft-warning') }}">
                                    {{ $status ?? '—' }}
                                </span>
                            </td>
                            <td class="small text-muted">{{ $user->created_at->format('d/m/Y') }}</td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    @if($status === 'suspendu')
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
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucun utilisateur trouvé.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">{{ $users->links() }}</div>
    </div>
@endsection

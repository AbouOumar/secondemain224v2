@extends('admin.layout')

@section('title', 'Livraisons')

@section('content')
    <div class="admin-topbar">
        <h1 class="h4 mb-0">Livraisons</h1>
    </div>

    <form method="GET" class="card p-3 mb-3 d-flex flex-row flex-wrap gap-2 align-items-end">
        <div>
            <label class="form-label small mb-1">Statut</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Tous</option>
                @foreach(['en_attente', 'assignee', 'acceptee', 'en_cours', 'livree', 'effectuee', 'annulee'] as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
            <a href="{{ route('admin.deliveries.index') }}" class="btn btn-outline-secondary btn-sm">Réinitialiser</a>
        </div>
    </form>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Commande</th>
                        <th>Livreur</th>
                        <th>Trajet</th>
                        <th>Statut</th>
                        <th>Créée le</th>
                        <th class="text-end">Changer le statut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deliveries as $delivery)
                        <tr>
                            <td class="small">{{ $delivery->order->reference ?? '—' }}</td>
                            <td class="small">{{ $delivery->rider->name ?? 'Non assigné' }}</td>
                            <td class="small text-muted">{{ $delivery->pickup_adresse }} → {{ $delivery->delivery_adresse }}</td>
                            <td>
                                @php $statusValue = $delivery->status->value ?? ''; @endphp
                                <span class="badge {{ in_array($statusValue, ['livree', 'effectuee']) ? 'badge-soft-success' : ($statusValue === 'annulee' ? 'badge-soft-danger' : 'badge-soft-warning') }}">
                                    {{ $statusValue }}
                                </span>
                            </td>
                            <td class="small text-muted">{{ $delivery->created_at->format('d/m/Y') }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.deliveries.update', $delivery) }}" class="d-flex gap-1 justify-content-end">
                                    @csrf
                                    @method('PUT')
                                    <select name="status" class="form-select form-select-sm" style="width:auto;">
                                        @foreach(['en_attente', 'assignee', 'acceptee', 'en_cours', 'livree', 'effectuee', 'annulee'] as $status)
                                            <option value="{{ $status }}" {{ $statusValue === $status ? 'selected' : '' }}>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-outline-secondary">Mettre à jour</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucune livraison.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">{{ $deliveries->links() }}</div>
    </div>
@endsection

@extends('admin.layout')

@section('title', 'Séquestre (escrow)')

@section('content')
    <div class="admin-topbar">
        <h1 class="h4 mb-0">Séquestre des paiements</h1>
        <div class="btn-group btn-group-sm">
            <a href="{{ route('admin.escrows.index', ['status' => 'retenu']) }}" class="btn btn-outline-secondary {{ $status === 'retenu' ? 'active' : '' }}">Retenus</a>
            <a href="{{ route('admin.escrows.index', ['status' => 'litige']) }}" class="btn btn-outline-secondary {{ $status === 'litige' ? 'active' : '' }}">En litige</a>
            <a href="{{ route('admin.escrows.index', ['status' => 'libere']) }}" class="btn btn-outline-secondary {{ $status === 'libere' ? 'active' : '' }}">Libérés</a>
            <a href="{{ route('admin.escrows.index', ['status' => 'rembourse']) }}" class="btn btn-outline-secondary {{ $status === 'rembourse' ? 'active' : '' }}">Remboursés</a>
            <a href="{{ route('admin.escrows.index', ['status' => 'all']) }}" class="btn btn-outline-secondary {{ $status === 'all' ? 'active' : '' }}">Tous</a>
        </div>
    </div>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Commande</th>
                        <th>Acheteur</th>
                        <th>Vendeur</th>
                        <th>Montant vendeur</th>
                        <th>Statut</th>
                        <th>Bloqué depuis</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($escrows as $escrow)
                        <tr>
                            <td class="small">
                                <a href="{{ url('/admin/paiements') }}">{{ $escrow->order->reference ?? '—' }}</a>
                                <div class="text-muted">{{ $escrow->order->article->titre ?? '' }}</div>
                            </td>
                            <td class="small">{{ $escrow->order->buyer->name ?? '—' }}</td>
                            <td class="small">{{ $escrow->order->seller->name ?? '—' }}</td>
                            <td class="small">{{ number_format($escrow->seller_amount, 0, ',', ' ') }} GNF</td>
                            <td>
                                <span class="badge {{ match($escrow->status->value) {
                                    'libere' => 'badge-soft-success',
                                    'rembourse' => 'badge-soft-info',
                                    'litige' => 'badge-soft-danger',
                                    default => 'badge-soft-warning',
                                } }}">{{ $escrow->status->value }}</span>
                            </td>
                            <td class="small text-muted">{{ $escrow->held_at->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                @if(in_array($escrow->status->value, ['retenu', 'litige']))
                                    <div class="d-flex gap-1 justify-content-end">
                                        <form method="POST" action="{{ route('admin.escrows.release', $escrow) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success" onclick="return confirm('Libérer les fonds au vendeur ?')">Libérer</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.escrows.refund', $escrow) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-info" onclick="return confirm('Rembourser l\'acheteur ?')">Rembourser</button>
                                        </form>
                                        @if($escrow->status->value !== 'litige')
                                            <form method="POST" action="{{ route('admin.escrows.dispute', $escrow) }}">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-danger">Marquer en litige</button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Aucun escrow.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">{{ $escrows->links() }}</div>
    </div>
@endsection

@extends('admin.layout')

@section('title', 'Paiements')

@section('content')
    <div class="admin-topbar">
        <h1 class="h4 mb-0">Paiements</h1>
        <div class="text-end">
            <div class="small text-muted">Total encaissé (filtre courant)</div>
            <div class="fw-bold">{{ number_format($totalComplete, 0, ',', ' ') }} GNF</div>
        </div>
    </div>

    <form method="GET" class="card p-3 mb-3 d-flex flex-row flex-wrap gap-2 align-items-end">
        <div>
            <label class="form-label small mb-1">Statut</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">Tous</option>
                @foreach(['en_attente', 'succes', 'echoue', 'annule'] as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="form-label small mb-1">Méthode</label>
            <select name="methode" class="form-select form-select-sm">
                <option value="">Toutes</option>
                @foreach(['orange_money', 'mtn_momo', 'carte_bancaire', 'portefeuille', 'djomy'] as $methode)
                    <option value="{{ $methode }}" {{ request('methode') === $methode ? 'selected' : '' }}>{{ str_replace('_', ' ', $methode) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
            <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary btn-sm">Réinitialiser</a>
        </div>
    </form>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Référence</th>
                        <th>Utilisateur</th>
                        <th>Commande</th>
                        <th>Montant</th>
                        <th>Méthode</th>
                        <th>Statut</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td><a href="{{ route('admin.payments.show', $payment) }}">{{ $payment->reference }}</a></td>
                            <td class="small">{{ $payment->user->name ?? '—' }}</td>
                            <td class="small text-muted">{{ $payment->order->reference ?? '—' }}</td>
                            <td class="small">{{ number_format($payment->montant, 0, ',', ' ') }} {{ $payment->currency->value ?? '' }}</td>
                            <td class="small text-muted">{{ str_replace('_', ' ', $payment->methode->value ?? '') }}</td>
                            <td>
                                @php $statusValue = $payment->status->value ?? ''; @endphp
                                <span class="badge {{ $statusValue === 'succes' ? 'badge-soft-success' : ($statusValue === 'echoue' ? 'badge-soft-danger' : 'badge-soft-warning') }}">
                                    {{ $statusValue }}
                                </span>
                            </td>
                            <td class="small text-muted">{{ $payment->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Aucun paiement.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">{{ $payments->links() }}</div>
    </div>
@endsection

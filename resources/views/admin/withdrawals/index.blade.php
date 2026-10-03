@extends('admin.layout')

@section('title', 'Retraits')

@section('content')
    <div class="admin-topbar">
        <h1 class="h4 mb-0">Retraits du portefeuille</h1>
        <div class="text-end">
            <div class="small text-muted">À verser (en attente)</div>
            <div class="fw-bold">{{ number_format($pendingTotal, 0, ',', ' ') }} GNF</div>
        </div>
    </div>

    <div class="mb-3 d-flex gap-2">
        @foreach (['en_attente' => 'En attente', 'paye' => 'Payés', 'refuse' => 'Refusés', 'tous' => 'Tous'] as $key => $label)
            <a href="{{ route('admin.withdrawals.index', ['status' => $key]) }}" class="btn btn-sm {{ $status === $key ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Demande</th>
                        <th>Utilisateur</th>
                        <th>Montant</th>
                        <th>Verser à</th>
                        <th>Statut</th>
                        <th style="min-width: 260px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($withdrawals as $withdrawal)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $withdrawal->reference }}</div>
                                <div class="small text-muted">{{ $withdrawal->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td>{{ $withdrawal->user->name }}<div class="small text-muted">{{ $withdrawal->user->email ?? $withdrawal->user->phone }}</div></td>
                            <td class="fw-bold">{{ number_format($withdrawal->montant, 0, ',', ' ') }} GNF</td>
                            <td>{{ $withdrawal->phone }}<div class="small text-muted">{{ $withdrawal->operator === 'orange_money' ? 'Orange Money' : 'MTN MoMo' }}</div></td>
                            <td>
                                @if ($withdrawal->status === 'en_attente')
                                    <span class="badge bg-warning text-dark">En attente</span>
                                @elseif ($withdrawal->status === 'paye')
                                    <span class="badge bg-success">Payé</span>
                                @else
                                    <span class="badge bg-secondary">Refusé</span>
                                @endif
                                @if ($withdrawal->note)<div class="small text-muted mt-1">{{ $withdrawal->note }}</div>@endif
                            </td>
                            <td>
                                @if ($withdrawal->status === 'en_attente')
                                    <form method="POST" action="{{ route('admin.withdrawals.pay', $withdrawal) }}" class="d-flex gap-1 mb-1">
                                        @csrf
                                        <input type="text" name="note" class="form-control form-control-sm" placeholder="Réf. de l'envoi (facultatif)">
                                        <button class="btn btn-success btn-sm" onclick="return confirm('Confirmer que l\'argent a été envoyé ?')">Payé</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.withdrawals.reject', $withdrawal) }}" class="d-flex gap-1">
                                        @csrf
                                        <input type="text" name="note" class="form-control form-control-sm" placeholder="Motif du refus">
                                        <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Refuser et rembourser le portefeuille ?')">Refuser</button>
                                    </form>
                                @else
                                    <span class="small text-muted">{{ $withdrawal->processed_at?->format('d/m/Y H:i') }}{{ $withdrawal->processor ? ' · ' . $withdrawal->processor->name : '' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucune demande.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $withdrawals->links() }}</div>
    </div>
@endsection

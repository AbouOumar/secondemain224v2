@extends('admin.layout')

@section('title', 'Vérifications d\'identité')

@section('content')
    <div class="admin-topbar">
        <h1 class="h4 mb-0">Vérifications d'identité</h1>
        <div class="btn-group btn-group-sm">
            <a href="{{ route('admin.verifications.index', ['status' => 'pending']) }}" class="btn btn-outline-secondary {{ $status === 'pending' ? 'active' : '' }}">En attente</a>
            <a href="{{ route('admin.verifications.index', ['status' => 'approved']) }}" class="btn btn-outline-secondary {{ $status === 'approved' ? 'active' : '' }}">Approuvées</a>
            <a href="{{ route('admin.verifications.index', ['status' => 'rejected']) }}" class="btn btn-outline-secondary {{ $status === 'rejected' ? 'active' : '' }}">Rejetées</a>
            <a href="{{ route('admin.verifications.index', ['status' => 'all']) }}" class="btn btn-outline-secondary {{ $status === 'all' ? 'active' : '' }}">Toutes</a>
        </div>
    </div>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Type de document</th>
                        <th>Statut</th>
                        <th>Soumis le</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($verifications as $verification)
                        <tr>
                            <td>{{ $verification->user->name ?? '—' }}</td>
                            <td class="small text-muted">{{ $verification->document_type }}</td>
                            <td>
                                <span class="badge {{ match($verification->status->value) {
                                    'approved' => 'badge-soft-success',
                                    'rejected' => 'badge-soft-danger',
                                    default => 'badge-soft-warning',
                                } }}">{{ $verification->status->value }}</span>
                            </td>
                            <td class="small text-muted">{{ $verification->submitted_at->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.verifications.show', $verification) }}" class="btn btn-sm btn-outline-secondary">Examiner</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Aucune vérification.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">{{ $verifications->links() }}</div>
    </div>
@endsection

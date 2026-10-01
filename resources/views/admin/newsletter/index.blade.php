@extends('admin.layout')

@section('title', 'Newsletter')

@section('content')
    <div class="admin-topbar">
        <div>
            <h1 class="h4 mb-0">Newsletter</h1>
            <span class="text-muted small">{{ $subscribersCount }} abonné(s) avec une adresse confirmée</span>
        </div>
        <a href="{{ route('admin.newsletter.create') }}" class="btn btn-primary btn-sm"><i class='bx bx-plus'></i> Nouvelle campagne</a>
    </div>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Objet</th>
                        <th>Statut</th>
                        <th>Envoyés</th>
                        <th>Créée le</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($campaigns as $campaign)
                        <tr>
                            <td>{{ $campaign->subject }}</td>
                            <td>
                                <span class="badge {{ match($campaign->status) {
                                    'sent' => 'badge-soft-success',
                                    'sending' => 'badge-soft-info',
                                    default => 'badge-soft-secondary',
                                } }}">{{ $campaign->statusLabel() }}</span>
                            </td>
                            <td class="small">
                                {{ $campaign->sent_count }}
                                @if($campaign->failed_count)
                                    <span class="text-danger">({{ $campaign->failed_count }} échec(s))</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $campaign->created_at->format('d/m/Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.newsletter.show', $campaign) }}" class="btn btn-sm btn-outline-secondary">Ouvrir</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Aucune campagne pour le moment.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">{{ $campaigns->links() }}</div>
    </div>
@endsection

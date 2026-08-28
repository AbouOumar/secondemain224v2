@extends('admin.layout')

@section('title', 'Signalements')

@section('content')
    <div class="admin-topbar">
        <h1 class="h4 mb-0">Signalements</h1>
        <div class="btn-group btn-group-sm">
            <a href="{{ route('admin.reports.index', ['status' => 'pending']) }}" class="btn btn-outline-secondary {{ $status === 'pending' ? 'active' : '' }}">En attente</a>
            <a href="{{ route('admin.reports.index', ['status' => 'reviewing']) }}" class="btn btn-outline-secondary {{ $status === 'reviewing' ? 'active' : '' }}">En cours</a>
            <a href="{{ route('admin.reports.index', ['status' => 'resolved']) }}" class="btn btn-outline-secondary {{ $status === 'resolved' ? 'active' : '' }}">Résolus</a>
            <a href="{{ route('admin.reports.index', ['status' => 'all']) }}" class="btn btn-outline-secondary {{ $status === 'all' ? 'active' : '' }}">Tous</a>
        </div>
    </div>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Cible</th>
                        <th>Motif</th>
                        <th>Signalé par</th>
                        <th>Statut</th>
                        <th>Le</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reports as $report)
                        <tr>
                            <td class="small">
                                <span class="badge badge-soft-secondary">{{ $report->reportable_type === \App\Models\Article::class ? 'Annonce' : 'Utilisateur' }}</span>
                                {{ $report->reportable?->titre ?? $report->reportable?->name ?? 'Supprimé' }}
                            </td>
                            <td class="small">{{ $report->reason->label() }}</td>
                            <td class="small text-muted">{{ $report->reporter->name ?? '—' }}</td>
                            <td>
                                <span class="badge {{ match($report->status->value) {
                                    'resolved' => 'badge-soft-success',
                                    'dismissed' => 'badge-soft-secondary',
                                    'reviewing' => 'badge-soft-info',
                                    default => 'badge-soft-warning',
                                } }}">{{ $report->status->value }}</span>
                            </td>
                            <td class="small text-muted">{{ $report->created_at->format('d/m/Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.reports.show', $report) }}" class="btn btn-sm btn-outline-secondary">Examiner</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucun signalement.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">{{ $reports->links() }}</div>
    </div>
@endsection

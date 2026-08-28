@extends('admin.layout')

@section('title', 'Signalement #' . $report->id)

@section('content')
    <div class="admin-topbar">
        <div>
            <a href="{{ route('admin.reports.index') }}" class="text-muted small"><i class='bx bx-arrow-back'></i> Signalements</a>
            <h1 class="h4 mb-0">Signalement #{{ $report->id }}</h1>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card p-3">
                <h2 class="h6 mb-3">Détails</h2>
                <dl class="row small mb-0">
                    <dt class="col-5">Type</dt>
                    <dd class="col-7">{{ $report->reportable_type === \App\Models\Article::class ? 'Annonce' : 'Utilisateur' }}</dd>
                    <dt class="col-5">Cible</dt>
                    <dd class="col-7">
                        @if($report->reportable instanceof \App\Models\Article)
                            <a href="{{ url('/articles/'.$report->reportable->slug) }}" target="_blank">{{ $report->reportable->titre }}</a>
                        @elseif($report->reportable)
                            <a href="{{ route('admin.users.show', $report->reportable) }}">{{ $report->reportable->name }}</a>
                        @else
                            <span class="text-muted">Supprimé</span>
                        @endif
                    </dd>
                    <dt class="col-5">Motif</dt><dd class="col-7">{{ $report->reason->label() }}</dd>
                    <dt class="col-5">Signalé par</dt><dd class="col-7">{{ $report->reporter->name ?? '—' }}</dd>
                    <dt class="col-5">Statut</dt><dd class="col-7">{{ $report->status->value }}</dd>
                    <dt class="col-5">Le</dt><dd class="col-7">{{ $report->created_at->format('d/m/Y H:i') }}</dd>
                    @if($report->description)
                        <dt class="col-12 mt-2">Description</dt>
                        <dd class="col-12">{{ $report->description }}</dd>
                    @endif
                    @if($report->resolved_at)
                        <dt class="col-5">Traité le</dt><dd class="col-7">{{ $report->resolved_at->format('d/m/Y H:i') }}</dd>
                        <dt class="col-5">Traité par</dt><dd class="col-7">{{ $report->resolver->name ?? '—' }}</dd>
                        @if($report->resolution_note)
                            <dt class="col-12 mt-2">Note de résolution</dt>
                            <dd class="col-12">{{ $report->resolution_note }}</dd>
                        @endif
                    @endif
                </dl>
            </div>
        </div>

        <div class="col-lg-7">
            @if(in_array($report->status->value, ['pending', 'reviewing']))
                <div class="card p-3">
                    <h2 class="h6 mb-3">Traiter ce signalement</h2>
                    <form method="POST" action="{{ route('admin.reports.resolve', $report) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Action</label>
                            <select name="action" class="form-select" required>
                                <option value="dismiss">Classer sans suite</option>
                                <option value="warn">Avertir (note interne uniquement)</option>
                                @if($report->reportable_type === \App\Models\Article::class)
                                    <option value="remove_content">Dépublier l'annonce</option>
                                @endif
                                <option value="suspend_user">Suspendre le compte concerné</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Note (facultatif)</label>
                            <textarea name="note" class="form-control" rows="3" placeholder="Détails de la décision..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Valider</button>
                    </form>
                </div>
            @else
                <div class="card p-3 text-muted small">Ce signalement a déjà été traité.</div>
            @endif
        </div>
    </div>
@endsection

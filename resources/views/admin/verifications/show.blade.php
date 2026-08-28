@extends('admin.layout')

@section('title', 'Vérification — ' . ($verification->user->name ?? ''))

@section('content')
    <div class="admin-topbar">
        <div>
            <a href="{{ route('admin.verifications.index') }}" class="text-muted small"><i class='bx bx-arrow-back'></i> Vérifications</a>
            <h1 class="h4 mb-0">{{ $verification->user->name }}</h1>
        </div>
        @if($verification->status->value === 'pending')
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('admin.verifications.approve', $verification) }}">
                    @csrf
                    <button class="btn btn-sm btn-success">Approuver</button>
                </form>
                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reject-modal">Rejeter</button>
            </div>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card p-3">
                <h2 class="h6 mb-3">Détails</h2>
                <dl class="row small mb-0">
                    <dt class="col-5">Utilisateur</dt><dd class="col-7"><a href="{{ route('admin.users.show', $verification->user) }}">{{ $verification->user->name }}</a></dd>
                    <dt class="col-5">Téléphone</dt><dd class="col-7">{{ $verification->user->phone }}</dd>
                    <dt class="col-5">Type de document</dt><dd class="col-7">{{ $verification->document_type }}</dd>
                    <dt class="col-5">Statut</dt><dd class="col-7">{{ $verification->status->value }}</dd>
                    <dt class="col-5">Soumis le</dt><dd class="col-7">{{ $verification->submitted_at->format('d/m/Y H:i') }}</dd>
                    @if($verification->reviewed_at)
                        <dt class="col-5">Examiné le</dt><dd class="col-7">{{ $verification->reviewed_at->format('d/m/Y H:i') }}</dd>
                        <dt class="col-5">Examiné par</dt><dd class="col-7">{{ $verification->reviewer->name ?? '—' }}</dd>
                    @endif
                    @if($verification->rejection_reason)
                        <dt class="col-5">Motif de rejet</dt><dd class="col-7">{{ $verification->rejection_reason }}</dd>
                    @endif
                </dl>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card p-3">
                <h2 class="h6 mb-3">Documents soumis</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <p class="small text-muted mb-1">Pièce d'identité</p>
                        <a href="{{ asset('storage/'.$verification->document_path) }}" target="_blank">
                            <img src="{{ asset('storage/'.$verification->document_path) }}" class="img-fluid rounded border" style="max-height:280px;object-fit:contain;">
                        </a>
                    </div>
                    <div class="col-md-6">
                        <p class="small text-muted mb-1">Selfie</p>
                        <a href="{{ asset('storage/'.$verification->selfie_path) }}" target="_blank">
                            <img src="{{ asset('storage/'.$verification->selfie_path) }}" class="img-fluid rounded border" style="max-height:280px;object-fit:contain;">
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reject-modal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.verifications.reject', $verification) }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Rejeter la demande</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small">Motif du rejet</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="Expliquez pourquoi la demande est rejetée..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Rejeter</button>
                </div>
            </form>
        </div>
    </div>
@endsection

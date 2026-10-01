@extends('admin.layout')

@section('title', $campaign->subject)

@section('content')
    <div class="admin-topbar">
        <div>
            <a href="{{ route('admin.newsletter.index') }}" class="text-muted small"><i class='bx bx-arrow-back'></i> Newsletter</a>
            <h1 class="h4 mb-0">{{ $campaign->subject }}</h1>
        </div>
        @if($campaign->isDraft())
            <a href="{{ route('admin.newsletter.edit', $campaign) }}" class="btn btn-outline-secondary btn-sm"><i class='bx bx-edit'></i> Modifier</a>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card p-3 mb-3">
                <h2 class="h6 mb-3">Statut</h2>
                <dl class="row small mb-0">
                    <dt class="col-6">Statut</dt><dd class="col-6">{{ $campaign->statusLabel() }}</dd>
                    <dt class="col-6">Envoyés</dt><dd class="col-6">{{ $campaign->sent_count }}</dd>
                    @if($campaign->failed_count)
                        <dt class="col-6">Échecs</dt><dd class="col-6 text-danger">{{ $campaign->failed_count }}</dd>
                    @endif
                    @if(! in_array($campaign->status, ['sent'], true))
                        <dt class="col-6">Restants</dt><dd class="col-6">{{ $pendingCount }}</dd>
                    @endif
                    @if($campaign->started_at)
                        <dt class="col-6">Lancée le</dt><dd class="col-6">{{ $campaign->started_at->format('d/m/Y H:i') }}</dd>
                    @endif
                    @if($campaign->sent_at)
                        <dt class="col-6">Terminée le</dt><dd class="col-6">{{ $campaign->sent_at->format('d/m/Y H:i') }}</dd>
                    @endif
                </dl>
                @if($campaign->status === 'sending' && $perMinute > 0)
                    <p class="small text-muted mt-3 mb-0">Environ {{ (int) ceil($pendingCount / $perMinute) }} minute(s) restante(s) ({{ $perMinute }} e-mails par minute).</p>
                @endif
            </div>

            <div class="card p-3">
                <h2 class="h6 mb-3">Actions</h2>
                <form method="POST" action="{{ route('admin.newsletter.test', $campaign) }}" class="mb-2">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary w-100"><i class='bx bx-send'></i> M'envoyer un test</button>
                </form>
                @if($campaign->isDraft())
                    <form method="POST" action="{{ route('admin.newsletter.send', $campaign) }}" class="mb-2"
                          onsubmit="return confirm('Envoyer cette campagne à {{ $pendingCount }} abonné(s) ? Cette action est définitive.');">
                        @csrf
                        <button type="submit" class="btn btn-primary w-100" @disabled($pendingCount === 0)><i class='bx bx-paper-plane'></i> Envoyer à {{ $pendingCount }} abonné(s)</button>
                    </form>
                    <form method="POST" action="{{ route('admin.newsletter.destroy', $campaign) }}" onsubmit="return confirm('Supprimer ce brouillon ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-link text-danger w-100 btn-sm">Supprimer le brouillon</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card p-0 overflow-hidden">
                <div class="px-3 py-2 border-bottom small text-muted">Aperçu</div>
                <iframe src="{{ route('admin.newsletter.preview', $campaign) }}" title="Aperçu de l'e-mail" style="width: 100%; height: 640px; border: 0;"></iframe>
            </div>
        </div>
    </div>
@endsection

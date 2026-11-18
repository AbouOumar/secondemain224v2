@extends('layouts.app')
@section('content')
<div class="container py-4">
    @include('profile.nav')

    <h2 class="fw-bold mb-4">Apporteur d'affaires</h2>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            @if($apporteur && $apporteur->isActive())
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <p class="text-muted mb-1">Votre code apporteur</p>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <span class="fs-2 fw-bold" style="letter-spacing: .15em;" id="apporteur-code">{{ $apporteur->code }}</span>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('apporteur-link').value); this.textContent='Lien copié';">
                                <i class="bx bx-copy"></i> Copier mon lien d'inscription
                            </button>
                        </div>
                        <input type="hidden" id="apporteur-link" value="{{ route('register', ['ref' => $apporteur->code]) }}">
                        <p class="small text-muted mt-2 mb-0">
                            Les vendeurs saisissent ce code à l'inscription (ou dans leur profil avant leur première vente).
                            Vous touchez {{ $sharePercent }} % de la commission de Seconde Main 224 sur la première vente de chacun, versés dans votre portefeuille.
                        </p>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    @foreach([
                        ['Vendeurs apportés', $stats['sellers']],
                        ['Avec une annonce en ligne', $stats['active_sellers']],
                        ['Premières ventes', $stats['first_sales']],
                        ['Gains versés (GNF)', number_format($stats['earned'], 0, ',', ' ')],
                    ] as [$label, $value])
                        <div class="col-6 col-md-3">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-body">
                                    <div class="fs-4 fw-bold">{{ $value }}</div>
                                    <div class="small text-muted">{{ $label }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Derniers gains</h5>
                        @forelse($gains as $gain)
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span>{{ $gain->seller?->name ?? 'Vendeur' }} <span class="small text-muted">· {{ $gain->created_at->format('d/m/Y') }}</span></span>
                                <span class="fw-semibold {{ $gain->status === 'verse' ? 'text-success' : 'text-muted' }}">
                                    {{ $gain->status === 'verse' ? '+' . number_format($gain->amount, 0, ',', ' ') . ' GNF' : 'Non versé' }}
                                </span>
                            </div>
                        @empty
                            <p class="text-muted mb-0">Pas encore de gain : dès qu'un vendeur que vous avez apporté vend son premier article, votre part apparaît ici.</p>
                        @endforelse
                    </div>
                </div>
            @elseif($apporteur && $apporteur->status === 'en_attente')
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold">Candidature en cours d'examen</h5>
                        <p class="text-muted">Envoyée le {{ $apporteur->created_at->format('d/m/Y') }}. Notre équipe vous contactera sur WhatsApp pour la formation, puis vous recevrez votre code.</p>
                        <form method="POST" action="{{ route('profile.apporteur.withdraw') }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline-secondary btn-sm" onclick="return confirm('Retirer votre candidature ?')">Retirer ma candidature</button>
                        </form>
                    </div>
                </div>
            @elseif($apporteur && $apporteur->status === 'suspendu')
                <div class="alert alert-warning">Votre accès apporteur est suspendu. Contactez l'équipe sur WhatsApp au +224 666 01 25 49.</div>
            @else
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="fw-bold">Devenez apporteur d'affaires</h5>
                        <p class="text-muted">
                            Trouvez des vendeurs dans votre quartier, aidez-les à publier avec votre code, et touchez {{ $sharePercent }} % de notre commission sur leur première vente.
                            Aucun diplôme exigé, aucun frais d'inscription.
                        </p>
                        @if($apporteur && $apporteur->status === 'refuse')
                            <div class="alert alert-secondary small">Votre précédente candidature n'a pas été retenue{{ $apporteur->note ? ' : ' . $apporteur->note : '' }}. Vous pouvez en envoyer une nouvelle.</div>
                        @endif
                        <form method="POST" action="{{ route('profile.apporteur.apply') }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Votre quartier *</label>
                                <input type="text" name="quartier" class="form-control @error('quartier') is-invalid @enderror" maxlength="100" required value="{{ old('quartier') }}" placeholder="Ex. Gbessia, Kaloum, Ratoma…">
                                @error('quartier')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Votre activité</label>
                                <input type="text" name="activite" class="form-control" maxlength="100" value="{{ old('activite') }}" placeholder="Étudiant, commerçant, motard…">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Pourquoi voulez-vous nous rejoindre ?</label>
                                <textarea name="motivation" class="form-control" rows="3" maxlength="500">{{ old('motivation') }}</textarea>
                            </div>
                            <button class="btn btn-primary">Envoyer ma candidature</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold">Vous êtes vendeur ?</h5>
                    @if($referrer)
                        <p class="text-muted mb-0">Votre compte est relié à l'apporteur <strong>{{ $referrer->name }}</strong>.</p>
                    @else
                        <p class="text-muted small">Un apporteur vous a aidé à vous inscrire ? Saisissez son code avant votre première vente.</p>
                        <form method="POST" action="{{ route('profile.apporteur.attach') }}">
                            @csrf
                            <div class="input-group">
                                <input type="text" name="code" class="form-control text-uppercase @error('code') is-invalid @enderror" maxlength="20" placeholder="Code apporteur" value="{{ old('code') }}" required>
                                <button class="btn btn-primary">Valider</button>
                                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

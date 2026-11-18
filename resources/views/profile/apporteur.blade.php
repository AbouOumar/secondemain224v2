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
                        <p class="text-muted mb-1">Votre code de parrainage</p>
                        <div class="fs-2 fw-bold mb-3" style="letter-spacing: .15em;">{{ $apporteur->code }}</div>
                        <label class="form-label small text-muted mb-1">Votre lien de parrainage</label>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" id="apporteur-link" value="{{ $shareLink }}" readonly>
                            <button type="button" class="btn btn-outline-primary" onclick="navigator.clipboard.writeText(document.getElementById('apporteur-link').value); this.textContent='Copié';">
                                <i class="bx bx-copy"></i> Copier
                            </button>
                            <a class="btn btn-success" target="_blank" rel="noopener"
                               href="https://wa.me/?text={{ rawurlencode("Vends tes biens d'occasion sur Seconde Main 224 : publication gratuite, paiement sécurisé. Inscris-toi avec mon code {$apporteur->code} : {$shareLink}") }}">
                                <i class="bx bxl-whatsapp"></i> WhatsApp
                            </a>
                        </div>
                        <p class="small text-muted mb-0">
                            Partagez ce lien ou donnez votre code : la personne qui s'inscrit est reliée à vous.
                            À sa première vente payée en ligne, vous touchez {{ $sharePercent }} % de la commission de Seconde Main 224, versés dans votre portefeuille.
                        </p>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    @foreach([
                        ['Personnes parrainées', $stats['sellers']],
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
                            <p class="text-muted mb-0">Pas encore de gain : dès qu'une personne que vous avez parrainée vend son premier article, votre part apparaît ici.</p>
                        @endforelse
                    </div>
                </div>
            @elseif($apporteur && $apporteur->status === 'suspendu')
                <div class="alert alert-warning">Votre code de parrainage est suspendu{{ $apporteur->note ? ' : ' . $apporteur->note : '' }}. Contactez l'équipe sur WhatsApp au +224 666 01 25 49.</div>
            @else
                <div class="alert alert-secondary">Les comptes administrateurs ne participent pas au programme apporteur.</div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="fw-bold">Quelqu'un vous a parrainé ?</h5>
                    @if($referrer)
                        <p class="text-muted mb-0">Votre compte est relié à <strong>{{ $referrer->name }}</strong>.</p>
                    @else
                        <p class="text-muted small">Saisissez le code de la personne qui vous a fait connaître Seconde Main 224, avant votre première vente.</p>
                        <form method="POST" action="{{ route('profile.apporteur.attach') }}">
                            @csrf
                            <div class="input-group">
                                <input type="text" name="code" class="form-control text-uppercase @error('code') is-invalid @enderror" maxlength="20" placeholder="Code de parrainage" value="{{ old('code') }}" required>
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

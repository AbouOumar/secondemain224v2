@extends('admin.layout')

@section('title', $campaign->exists ? 'Modifier la campagne' : 'Nouvelle campagne')

@section('content')
    <div class="admin-topbar">
        <div>
            <a href="{{ route('admin.newsletter.index') }}" class="text-muted small"><i class='bx bx-arrow-back'></i> Newsletter</a>
            <h1 class="h4 mb-0">{{ $campaign->exists ? 'Modifier la campagne' : 'Nouvelle campagne' }}</h1>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card p-3">
                @if ($errors->any())
                    <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ $campaign->exists ? route('admin.newsletter.update', $campaign) : route('admin.newsletter.store') }}">
                    @csrf
                    @if($campaign->exists)
                        @method('PUT')
                    @endif
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Objet de l'e-mail</label>
                        <input type="text" name="subject" class="form-control" maxlength="150" required value="{{ old('subject', $campaign->subject) }}" placeholder="Ex. : Les bons plans de la rentrée">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Contenu</label>
                        <textarea name="content" class="form-control font-monospace" rows="16" required placeholder="Écrivez votre message...">{{ old('content', $campaign->content) }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Enregistrer le brouillon</button>
                </form>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card p-3 small">
                <h2 class="h6">Mise en forme</h2>
                <p class="text-muted mb-2">« Bonjour [prénom], » est ajouté automatiquement, ainsi que le lien de désinscription.</p>
                <ul class="text-muted ps-3 mb-0">
                    <li>Ligne vide = nouveau paragraphe</li>
                    <li><code>**texte**</code> = <strong>gras</strong></li>
                    <li><code>## Titre</code> = intertitre</li>
                    <li><code>- élément</code> = liste à puces</li>
                    <li><code>[texte](https://...)</code> = lien</li>
                    <li><code>![image](https://...)</code> = image</li>
                </ul>
            </div>
        </div>
    </div>
@endsection

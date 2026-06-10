@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-4">
                    <h4 class="card-title mb-0">Créer votre magasin</h4>
                    <p class="text-muted mb-0 mt-1">Configurez votre boutique pour commencer à vendre</p>
                </div>
                <div class="card-body p-4">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('magasin.store') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-medium">Nom du magasin *</label>
                            <input type="text" name="nom_magasin" class="form-control form-control-lg" value="{{ old('nom_magasin') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Slug (URL)</label>
                            <input type="text" name="slug" class="form-control form-control-lg" value="{{ old('slug') }}" placeholder="Laissez vide pour auto-génération">
                            <small class="text-muted">L'URL publique sera : /boutique/votre-slug</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Description</label>
                            <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Adresse</label>
                            <input type="text" name="adresse" class="form-control form-control-lg" value="{{ old('adresse') }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Téléphone</label>
                            <input type="tel" name="telephone" class="form-control form-control-lg" value="{{ old('telephone') }}">
                            <small class="text-muted">Téléphone professionnel (affiché sur la page du magasin)</small>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Logo</label>
                                <input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png">
                                <small class="text-muted">Max 2 Mo. JPG, PNG.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Bannière (couverture)</label>
                                <input type="file" name="couverture" class="form-control" accept=".jpg,.jpeg,.png">
                                <small class="text-muted">Max 5 Mo. JPG, PNG.</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-medium">Horaires d'ouverture</label>
                            <div id="horaires-container">
                                <div class="row g-2 mb-2 horaire-row">
                                    <div class="col-4">
                                        <select name="horaire[jours][]" class="form-control">
                                            <option value="Lundi">Lundi</option>
                                            <option value="Mardi">Mardi</option>
                                            <option value="Mercredi">Mercredi</option>
                                            <option value="Jeudi">Jeudi</option>
                                            <option value="Vendredi">Vendredi</option>
                                            <option value="Samedi">Samedi</option>
                                            <option value="Dimanche">Dimanche</option>
                                        </select>
                                    </div>
                                    <div class="col-3">
                                        <input type="time" name="horaire[ouverture][]" class="form-control" value="08:00">
                                    </div>
                                    <div class="col-3">
                                        <input type="time" name="horaire[fermeture][]" class="form-control" value="18:00">
                                    </div>
                                    <div class="col-2">
                                        <button type="button" class="btn btn-outline-danger btn-sm add-horaire w-100">+</button>
                                    </div>
                                </div>
                            </div>
                            <small class="text-muted">Ajoutez les jours et horaires d'ouverture de votre magasin.</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Créer mon magasin</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.add-horaire')?.addEventListener('click', function() {
        const container = document.getElementById('horaires-container');
        const row = container.querySelector('.horaire-row').cloneNode(true);
        row.querySelectorAll('select, input').forEach(el => { el.value = ''; });
        row.querySelector('select').value = 'Lundi';
        row.querySelector('input[type="time"]:first-of-type').value = '08:00';
        row.querySelector('input[type="time"]:last-of-type').value = '18:00';
        const btn = row.querySelector('.add-horaire');
        btn.textContent = '×';
        btn.classList.remove('btn-outline-danger');
        btn.classList.add('btn-danger');
        btn.onclick = function() { this.closest('.horaire-row').remove(); };
        container.appendChild(row);
    });
});
</script>
@endpush

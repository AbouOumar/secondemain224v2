@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title mb-0">Modifier mon magasin</h4>
                        <p class="text-muted mb-0 mt-1">{{ $magasin->nom_magasin }}</p>
                    </div>
                    <a href="{{ $magasin->url }}" class="btn btn-outline-primary" target="_blank">
                        <i class='bx bx-show'></i> Voir la boutique
                    </a>
                </div>
                <div class="card-body p-4">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('magasin.update') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-medium">Nom du magasin *</label>
                            <input type="text" name="nom_magasin" class="form-control form-control-lg" value="{{ old('nom_magasin', $magasin->nom_magasin) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Slug (URL)</label>
                            <input type="text" name="slug" class="form-control form-control-lg" value="{{ old('slug', $magasin->slug) }}">
                            <small class="text-muted">URL publique : /boutique/{{ $magasin->slug }}</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Description</label>
                            <textarea name="description" class="form-control" rows="4">{{ old('description', $magasin->description) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Adresse</label>
                            <input type="text" name="adresse" class="form-control form-control-lg" value="{{ old('adresse', $magasin->adresse) }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium">Téléphone</label>
                            <input type="tel" name="telephone" class="form-control form-control-lg" value="{{ old('telephone', $magasin->telephone) }}">
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Logo</label>
                                @if($magasin->logo)
                                    <div class="mb-2"><img src="{{ $magasin->logo_url }}" alt="Logo" style="height:60px;border-radius:8px;"></div>
                                @endif
                                <input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png">
                                <small class="text-muted">Max 2 Mo. Laissez vide pour garder l'actuel.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Bannière (couverture)</label>
                                @if($magasin->couverture)
                                    <div class="mb-2"><img src="{{ $magasin->cover_url }}" alt="Couverture" style="height:60px;border-radius:8px;object-fit:cover;width:100%;"></div>
                                @endif
                                <input type="file" name="couverture" class="form-control" accept=".jpg,.jpeg,.png">
                                <small class="text-muted">Max 5 Mo. Laissez vide pour garder l'actuelle.</small>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-medium">Horaires d'ouverture</label>
                            <div id="horaires-container">
                                @forelse(old('horaire.jours', $magasin->horaire ?? []) as $i => $jour)
                                    @if(is_array($jour))
                                        @php $h = $jour; @endphp
                                        <div class="row g-2 mb-2 horaire-row">
                                            <div class="col-4">
                                                <select name="horaire[jours][]" class="form-control">
                                                    @foreach(['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'] as $d)
                                                        <option value="{{ $d }}" {{ ($h['jour'] ?? '') === $d ? 'selected' : '' }}>{{ $d }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-3"><input type="time" name="horaire[ouverture][]" class="form-control" value="{{ $h['ouverture'] ?? '08:00' }}"></div>
                                            <div class="col-3"><input type="time" name="horaire[fermeture][]" class="form-control" value="{{ $h['fermeture'] ?? '18:00' }}"></div>
                                            <div class="col-2"><button type="button" class="btn btn-danger btn-sm w-100 remove-horaire">×</button></div>
                                        </div>
                                    @elseif(is_string($jour))
                                        @php
                                            $jours = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];
                                            $label = $jour;
                                            $ouvert = old('horaire.ouverture', $magasin->horaire ?? [])[$i] ?? '08:00';
                                            $fermeture = old('horaire.fermeture', $magasin->horaire ?? [])[$i] ?? '18:00';
                                        @endphp
                                        <div class="row g-2 mb-2 horaire-row">
                                            <div class="col-4">
                                                <select name="horaire[jours][]" class="form-control">
                                                    @foreach($jours as $d)
                                                        <option value="{{ $d }}" {{ $d === $label ? 'selected' : '' }}>{{ $d }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-3"><input type="time" name="horaire[ouverture][]" class="form-control" value="{{ $ouvert }}"></div>
                                            <div class="col-3"><input type="time" name="horaire[fermeture][]" class="form-control" value="{{ $fermeture }}"></div>
                                            <div class="col-2"><button type="button" class="btn btn-danger btn-sm w-100 remove-horaire">×</button></div>
                                        </div>
                                    @endif
                                @empty
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
                                        <div class="col-3"><input type="time" name="horaire[ouverture][]" class="form-control" value="08:00"></div>
                                        <div class="col-3"><input type="time" name="horaire[fermeture][]" class="form-control" value="18:00"></div>
                                        <div class="col-2"><button type="button" class="btn btn-outline-danger btn-sm add-horaire w-100">+</button></div>
                                    </div>
                                @endforelse
                            </div>
                            <small class="text-muted">Ajoutez les jours et horaires d'ouverture de votre magasin.</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Enregistrer les modifications</button>
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
    const container = document.getElementById('horaires-container');

    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-horaire')) {
            const row = container.querySelector('.horaire-row').cloneNode(true);
            row.querySelectorAll('select, input').forEach(el => { el.value = ''; });
            row.querySelector('select').value = 'Lundi';
            row.querySelector('input[type="time"]:first-of-type').value = '08:00';
            row.querySelector('input[type="time"]:last-of-type').value = '18:00';
            const btn = row.querySelector('.add-horaire');
            if (btn) {
                btn.textContent = '×';
                btn.classList.remove('btn-outline-danger', 'add-horaire');
                btn.classList.add('btn-danger', 'remove-horaire');
                btn.onclick = null;
            }
            container.appendChild(row);
        }
        if (e.target.classList.contains('remove-horaire')) {
            const rows = container.querySelectorAll('.horaire-row');
            if (rows.length > 1) {
                e.target.closest('.horaire-row').remove();
            }
        }
    });
});
</script>
@endpush

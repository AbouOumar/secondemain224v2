@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-4">
                    <h4 class="card-title mb-0">Vérification du Revendeur</h4>
                </div>
                <div class="card-body p-4">
                    @if (auth()->user()->is_verified)
                        <div class="alert alert-success mb-4">
                            <i class='bx bx-check-circle me-2'></i>
                            Félicitations ! Vous êtes un revendeur vérifié.
                            Votre badge sera affiché sur toutes vos annonces.
                        </div>
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="border rounded p-3">
                                    <h5 class="mb-3">Informations de vérification</h5>
                                    <p><strong>Statut :</strong> <span class="badge bg-success">Vérifié</span></p>
                                    <p><strong>Date de vérification :</strong> 
                                        {{ auth()->user()->verified_at->format('d/m/Y') }}
                                    </p>
                                    @if (auth()->user()->verification_documents)
                                        <p><strong>Type de document :</strong> 
                                            {{ ucfirst(auth()->user()->verification_documents.type ?? 'Non spécifié') }}
                                        </p>
                                        <p><strong>Soumis le :</strong> 
                                            {{ isset(auth()->user()->verification_documents.submitted_at) ? 
                                                (new DateTime(auth()->user()->verification_documents.submitted_at))->format('d/m/Y H:i') : 
                                                'Non disponible'
                                            }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="border rounded p-3 text-center">
                                    <div class="mb-3">
                                        <i class='bx bx-shield fs-1 text-success'></i>
                                    </div>
                                    <h5 class="mb-3">Badge vérifié</h5>
                                    <p class="text-muted">
                                        Ce badge sera affiché sur toutes vos annonces pour augmenter la confiance des acheteurs.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        @if (session('verification_status'))
                            <div class="alert alert-{{ session('verification_status') === 'success' ? 'success' : 'danger' }}">
                                {{ session('verification_message') }}
                            </div>
                        @endif

                        <form id="verification-form" method="POST" enctype="multipart/form-data">
                            @csrf
                            
                            <h5 class="mb-4">Soumettre vos documents de vérification</h5>
                            <p class="text-muted mb-4">
                                Pour devenir un revendeur vérifié, veuillez soumettre un pièce d'identité valide et un selfie.
                                Cette vérification augmente la confiance des acheteurs et vous donne accès au badge vérifié.
                            </p>

                            <div class="mb-4">
                                <label class="form-label fw-medium">Type de document *</label>
                                <select name="document_type" class="form-control form-control-lg" required>
                                    <option value="">Sélectionnez un type de document</option>
                                    <option value="id_card">Carte d'identité nationale</option>
                                    <option value="passport">Passeport</option>
                                    <option value="business_license">Licence d'entreprise</option>
                                    <option value="tax_document">Document fiscal</option>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-medium">Pièce d'identité *</label>
                                <input type="file" name="document" class="form-control form-control-lg" 
                                       accept=".jpg,.jpeg,.png,.pdf" required>
                                <small class="text-muted">Formats acceptés : JPG, JPEG, PNG, PDF. Taille max : 5 Mo.</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-medium">Selfie *</label>
                                <input type="file" name="selfie" class="form-control form-control-lg" 
                                       accept=".jpg,.jpeg,.png" required>
                                <small class="text-muted">Formats acceptés : JPG, JPEG, PNG. Taille max : 2 Mo.</small>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        Le selfie doit montrer clairement votre visage correspondant à la pièce d'identité.
                                    </small>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg" id="submit-verification">
                                    Soumettre pour vérification
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('verification-form');
    const submitBtn = document.getElementById('submit-verification');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (!form || !csrfToken) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const docFile = form.querySelector('input[name="document"]').files[0];
        const selfieFile = form.querySelector('input[name="selfie"]').files[0];

        if (docFile && docFile.size > 5 * 1024 * 1024) {
            alert('La pièce d\'identité ne doit pas dépasser 5 Mo');
            return;
        }
        if (selfieFile && selfieFile.size > 2 * 1024 * 1024) {
            alert('Le selfie ne doit pas dépasser 2 Mo');
            return;
        }

        submitBtn.disabled = true;
        submitBtn.textContent = 'Envoi en cours...';

        const formData = new FormData(form);

        fetch('{{ route("api.verification.submit") }}', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: formData,
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'pending') {
                location.reload();
            } else {
                alert(data.message || 'Erreur lors de l\'envoi');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Soumettre pour vérification';
            }
        })
        .catch(() => {
            alert('Erreur réseau. Veuillez réessayer.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Soumettre pour vérification';
        });
    });
});
</script>
@endpush
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-4">
                    <h4 class="card-title mb-0">Vérification d'identité</h4>
                </div>
                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('info'))
                        <div class="alert alert-info">{{ session('info') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($user->is_verified)
                        <div class="alert alert-success mb-4">
                            <i class='bx bx-check-circle me-2'></i>
                            Félicitations ! Votre identité est vérifiée.
                            Le badge « Vendeur vérifié » s'affiche désormais sur toutes vos annonces.
                        </div>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="border rounded p-3">
                                    <h5 class="mb-3">Informations de vérification</h5>
                                    <p><strong>Statut :</strong> <span class="badge bg-success">Vérifié</span></p>
                                    <p class="mb-0"><strong>Date de vérification :</strong>
                                        {{ $user->verified_at?->format('d/m/Y') ?? '—' }}
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="border rounded p-3 text-center">
                                    <div class="mb-3">
                                        <i class='bx bxs-badge-check fs-1 text-success'></i>
                                    </div>
                                    <h5 class="mb-3">Badge vérifié</h5>
                                    <p class="text-muted mb-0">
                                        Ce badge augmente la confiance des acheteurs sur toutes vos annonces.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @elseif($latest && $latest->status->value === 'pending')
                        <div class="alert alert-warning mb-0">
                            <i class='bx bx-time-five me-2'></i>
                            Votre demande du {{ $latest->submitted_at->format('d/m/Y H:i') }} est en cours d'examen par notre équipe.
                        </div>
                    @else
                        @if($latest && $latest->status->value === 'rejected')
                            <div class="alert alert-danger">
                                <strong>Votre précédente demande a été rejetée.</strong>
                                @if($latest->rejection_reason)
                                    <div class="mt-1">Motif : {{ $latest->rejection_reason }}</div>
                                @endif
                                Vous pouvez soumettre une nouvelle demande ci-dessous.
                            </div>
                        @endif

                        <form method="POST" action="{{ route('profile.verification.store') }}" enctype="multipart/form-data">
                            @csrf

                            <h5 class="mb-4">Soumettre vos documents de vérification</h5>
                            <p class="text-muted mb-4">
                                Pour obtenir le badge « Vendeur vérifié », soumettez une pièce d'identité valide et un selfie.
                                Cette vérification augmente la confiance des acheteurs.
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
                                <button type="submit" class="btn btn-primary btn-lg">
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

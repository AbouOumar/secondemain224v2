@extends('layouts.app')

@section('content')
<div class="container py-5">
<div class="row justify-content-center">
<div class="col-md-8 col-lg-6">
<div class="card shadow-sm border-0" style="border-radius: 18px;">
<div class="card-body p-5">
<div class="text-center mb-4">
<img src="{{ asset('assets/img/icon.png') }}" width="72" style="border-radius: 10px;">
<h3 class="mt-3 fw-bold">Créer un compte</h3>
</div>

@if ($errors->any())
<div class="alert alert-danger py-2">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('register') }}" id="register-form">
@csrf
<div class="mb-3 position-relative">
<i class="bx bx-user position-absolute" style="left: 18px; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem;"></i>
<input type="text" name="name" class="form-control" style="height: 52px; border-radius: 25px; padding-left: 45px;" placeholder="Nom complet *" required value="{{ old('name') }}">
</div>
<div class="mb-3 position-relative">
<i class="bx bx-envelope position-absolute" style="left: 18px; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem;"></i>
<input type="email" name="email" class="form-control" style="height: 52px; border-radius: 25px; padding-left: 45px;" placeholder="Adresse email *" required value="{{ old('email') }}">
</div>

<div class="mb-3">
    <div class="input-group" style="border-radius: 25px; overflow: hidden;">
        <select id="phone_code" class="form-select flex-grow-0" style="max-width: 115px; border-radius: 0;">
            <option value="+224" selected>🇬🇳 +224</option>
            <option value="+221">🇸🇳 +221</option>
            <option value="+223">🇲🇱 +223</option>
            <option value="+225">🇨🇮 +225</option>
            <option value="+232">🇸🇱 +232</option>
            <option value="+231">🇱🇷 +231</option>
            <option value="+245">🇬🇼 +245</option>
            <option value="+33">🇫🇷 +33</option>
        </select>
        <input type="text" id="phone_number" class="form-control" placeholder="6XX XX XX XX *" required inputmode="numeric" autocomplete="tel-national">
    </div>
    <input type="hidden" name="phone" id="phone_hidden" value="{{ old('phone') }}">
</div>

<div class="mb-3 position-relative">
<i class="bx bx-lock position-absolute" style="left: 18px; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem;"></i>
<input type="password" name="password" id="password" class="form-control" style="height: 52px; border-radius: 25px; padding-left: 45px; padding-right: 45px;" placeholder="Mot de passe *" required>
<button type="button" class="btn position-absolute p-0 border-0 bg-transparent" style="right: 15px; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem; line-height: 1;" onclick="togglePasswordVisibility('password', this)" aria-label="Afficher/masquer le mot de passe">
    <i class='bx bx-show'></i>
</button>
</div>
<div class="mb-4 position-relative">
<i class="bx bx-lock-alt position-absolute" style="left: 18px; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem;"></i>
<input type="password" name="password_confirmation" id="password_confirmation" class="form-control" style="height: 52px; border-radius: 25px; padding-left: 45px; padding-right: 45px;" placeholder="Confirmer le mot de passe *" required>
<button type="button" class="btn position-absolute p-0 border-0 bg-transparent" style="right: 15px; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem; line-height: 1;" onclick="togglePasswordVisibility('password_confirmation', this)" aria-label="Afficher/masquer le mot de passe">
    <i class='bx bx-show'></i>
</button>
</div>
<div class="mb-4 position-relative">
<i class="bx bx-badge-check position-absolute" style="left: 18px; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem;"></i>
<select name="role" class="form-control" style="height: 52px; border-radius: 25px; padding-left: 45px;" required>
<option value="">Sélectionnez un rôle *</option>
<option value="acheteur" {{ old('role') === 'acheteur' ? 'selected' : '' }}>Acheteur</option>
<option value="vendeur" {{ old('role') === 'vendeur' ? 'selected' : '' }}>Vendeur</option>
<option value="revendeur_pro" {{ old('role') === 'revendeur_pro' ? 'selected' : '' }}>Revendeur</option>
<option value="motard" {{ old('role') === 'motard' ? 'selected' : '' }}>Motard</option>
</select>
</div>
<button type="submit" class="btn btn-primary w-100" style="border-radius: 25px; padding: 12px; font-weight: 600;">S'inscrire</button>
<hr class="my-4">
<div class="text-center small">
<a href="{{ route('login') }}">Déjà un compte ? Connectez-vous</a>
</div>
</form>
</div>
</div>
</div>
</div>
</div>

@push('scripts')
<script>
    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        const showing = input.type === 'password';
        input.type = showing ? 'text' : 'password';
        icon.classList.toggle('bx-show', !showing);
        icon.classList.toggle('bx-hide', showing);
    }

    document.getElementById('register-form').addEventListener('submit', function () {
        const code = document.getElementById('phone_code').value;
        const number = document.getElementById('phone_number').value.replace(/\s+/g, '');
        document.getElementById('phone_hidden').value = number ? code + number : '';
    });
</script>
@endpush
@endsection

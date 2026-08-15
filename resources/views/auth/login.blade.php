@extends('layouts.app')

@section('content')
<div class="container py-5">
<div class="row justify-content-center">
<div class="col-md-6 col-lg-5">
<div class="card shadow-sm border-0" style="border-radius: 18px;">
<div class="card-body p-5">
<div class="text-center mb-4">
<img src="{{ asset('assets/img/icon.png') }}" width="72" style="border-radius: 10px;">
<h3 class="mt-3 fw-bold">Seconde Main 224</h3>
</div>

@if ($errors->any())
<div class="alert alert-danger py-2">{{ $errors->first('login') }}</div>
@endif

<form method="POST" action="{{ route('login') }}">
@csrf
<div class="mb-3 position-relative">
<i class="bx bx-user position-absolute" style="left: 18px; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem;"></i>
<input type="text" name="login" class="form-control" style="height: 52px; border-radius: 25px; padding-left: 45px;" placeholder="Email ou Téléphone *" required value="{{ old('login') }}">
</div>
<div class="mb-4 position-relative">
<i class="bx bx-lock position-absolute" style="left: 18px; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem;"></i>
<input type="password" name="password" id="loginPassword" class="form-control" style="height: 52px; border-radius: 25px; padding-left: 45px; padding-right: 45px;" placeholder="Mot de passe" required>
<span onclick="togglePassword()" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--gray-500);font-size:1.3rem;">
<i class="bx bx-show" id="passwordToggleIcon"></i>
</span>
</div>
<script>
function togglePassword() {
const input = document.getElementById('loginPassword');
const icon = document.getElementById('passwordToggleIcon');
if (input.type === 'password') {
input.type = 'text';
icon.className = 'bx bx-hide';
} else {
input.type = 'password';
icon.className = 'bx bx-show';
}
}
</script>
<div class="d-flex justify-content-between align-items-center mb-4">
<div class="form-check">
<input class="form-check-input" type="checkbox" name="remember" id="remember">
<label class="form-check-label" for="remember">Se souvenir</label>
</div>
<a href="{{ route('password.request') }}" class="small">Mot de passe oublié ?</a>
</div>
<button type="submit" class="btn btn-primary w-100" style="border-radius: 25px; padding: 12px; font-weight: 600;">Se connecter</button>
</form>

<div class="d-flex align-items-center my-4">
<hr class="flex-grow-1"><span class="px-3 small text-muted">ou</span><hr class="flex-grow-1">
</div>

<a href="{{ route('auth.google.redirect') }}" class="btn btn-outline-secondary w-100 d-flex align-items-center justify-content-center gap-2" style="border-radius: 25px; padding: 12px; font-weight: 600;">
<svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg"><path fill="#4285F4" d="M17.64 9.2c0-.64-.06-1.25-.16-1.84H9v3.48h4.84c-.21 1.13-.84 2.09-1.8 2.73v2.27h2.91c1.7-1.57 2.69-3.87 2.69-6.64z"/><path fill="#34A853" d="M9 18c2.43 0 4.47-.8 5.96-2.17l-2.91-2.27c-.81.54-1.84.86-3.05.86-2.35 0-4.34-1.58-5.05-3.71H.98v2.34C2.46 15.98 5.48 18 9 18z"/><path fill="#FBBC05" d="M3.95 10.71a5.4 5.4 0 010-3.42V4.95H.98a9 9 0 000 8.1l2.97-2.34z"/><path fill="#EA4335" d="M9 3.58c1.32 0 2.51.45 3.44 1.35l2.58-2.58C13.46.89 11.43 0 9 0 5.48 0 2.46 2.02.98 4.95l2.97 2.34C4.66 5.16 6.65 3.58 9 3.58z"/></svg>
Continuer avec Google
</a>

<div class="text-center small mt-4">
<a href="{{ route('register') }}">Créer un compte</a>
</div>
</div>
</div>
</div>
</div>
</div>
@endsection

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
</div>
<button type="submit" class="btn btn-primary w-100" style="border-radius: 25px; padding: 12px; font-weight: 600;">Se connecter</button>
<hr class="my-4">
<div class="text-center small">
<a href="{{ route('register') }}">Créer un compte</a>
</div>
</form>
</div>
</div>
</div>
</div>
</div>
@endsection

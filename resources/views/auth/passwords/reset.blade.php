@extends('layouts.app')

@section('content')
<div class="container py-5">
<div class="row justify-content-center">
<div class="col-md-6 col-lg-5">
<div class="card shadow-sm border-0" style="border-radius: 18px;">
<div class="card-body p-5">
<div class="text-center mb-4">
<img src="{{ asset('assets/img/icon.png') }}" width="72" style="border-radius: 10px;">
<h3 class="mt-3 fw-bold">Nouveau mot de passe</h3>
</div>

@if ($errors->any())
<div class="alert alert-danger py-2">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('password.store') }}">
@csrf
<input type="hidden" name="token" value="{{ $token }}">
<div class="mb-3 position-relative">
<i class="bx bx-envelope position-absolute" style="left: 18px; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem;"></i>
<input type="email" name="email" class="form-control" style="height: 52px; border-radius: 25px; padding-left: 45px;" placeholder="Adresse e-mail" required value="{{ $email ?? old('email') }}" readonly>
</div>
<div class="mb-3 position-relative">
<i class="bx bx-lock position-absolute" style="left: 18px; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem;"></i>
<input type="password" name="password" class="form-control" style="height: 52px; border-radius: 25px; padding-left: 45px; padding-right: 45px;" placeholder="Nouveau mot de passe (min. 8 caractères)" required autofocus>
</div>
<div class="mb-4 position-relative">
<i class="bx bx-lock-alt position-absolute" style="left: 18px; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem;"></i>
<input type="password" name="password_confirmation" class="form-control" style="height: 52px; border-radius: 25px; padding-left: 45px;" placeholder="Confirmer le mot de passe" required>
</div>
<button type="submit" class="btn btn-primary w-100" style="border-radius: 25px; padding: 12px; font-weight: 600;">Réinitialiser le mot de passe</button>
</form>
</div>
</div>
</div>
</div>
</div>
@endsection

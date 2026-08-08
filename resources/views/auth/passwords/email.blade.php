@extends('layouts.app')

@section('content')
<div class="container py-5">
<div class="row justify-content-center">
<div class="col-md-6 col-lg-5">
<div class="card shadow-sm border-0" style="border-radius: 18px;">
<div class="card-body p-5">
<div class="text-center mb-4">
<img src="{{ asset('assets/img/icon.png') }}" width="72" style="border-radius: 10px;">
<h3 class="mt-3 fw-bold">Mot de passe oublié</h3>
<p class="text-muted small">Saisissez votre adresse e-mail pour recevoir un lien de réinitialisation.</p>
</div>

@if (session('status'))
<div class="alert alert-success py-2">{{ session('status') }}</div>
@endif

@if ($errors->any())
<div class="alert alert-danger py-2">{{ $errors->first('email') }}</div>
@endif

<form method="POST" action="{{ route('password.email') }}">
@csrf
<div class="mb-4 position-relative">
<i class="bx bx-envelope position-absolute" style="left: 18px; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 1.2rem;"></i>
<input type="email" name="email" class="form-control" style="height: 52px; border-radius: 25px; padding-left: 45px;" placeholder="Adresse e-mail" required value="{{ old('email') }}" autofocus>
</div>
<button type="submit" class="btn btn-primary w-100" style="border-radius: 25px; padding: 12px; font-weight: 600;">Envoyer le lien</button>
<hr class="my-4">
<div class="text-center small">
<a href="{{ route('login') }}">Retour à la connexion</a>
</div>
</form>
</div>
</div>
</div>
</div>
</div>
@endsection

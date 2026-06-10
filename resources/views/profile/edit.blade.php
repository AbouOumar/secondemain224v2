@extends('layouts.app')

@section('content')
<div class="container py-4">
@include('profile.nav')

<h2 class="mb-4">Mon profil</h2>

<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card border-0 shadow-sm" style="border-radius: 18px;">
<div class="card-body p-4">

@if(session('success'))
<div class="alert alert-success py-2">{{ session('success') }}</div>
@endif

@if ($errors->any())
<div class="alert alert-danger py-2">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
@csrf
@method('PUT')

<h5 class="fw-bold mb-3">Informations générales</h5>

<div class="mb-3 d-flex align-items-center gap-3">
<div>
@if($user->avatar)
<img src="{{ asset('storage/' . $user->avatar) }}" width="80" height="80" style="border-radius: 50%; object-fit: cover;">
@else
<div class="d-flex align-items-center justify-content-center bg-light" style="width: 80px; height: 80px; border-radius: 50%;">
<i class="bx bx-user" style="font-size: 2.5rem; color: #94a3b8;"></i>
</div>
@endif
</div>
<div>
<input type="file" name="avatar" class="form-control">
<small class="text-muted">PNG, JPG. Max 2 Mo.</small>
</div>
</div>

<div class="mb-3">
<label class="form-label">Nom complet</label>
<input type="text" name="name" class="form-control form-control-lg" value="{{ old('name', $user->name) }}" required>
</div>

<div class="mb-3">
<label class="form-label">Email</label>
<input type="email" name="email" class="form-control form-control-lg" value="{{ old('email', $user->email) }}" required>
</div>

<div class="mb-4">
<label class="form-label">Téléphone</label>
<input type="text" name="phone" class="form-control form-control-lg" value="{{ old('phone', $user->phone) }}" required>
</div>

<hr>

<h5 class="fw-bold mb-3">Changer le mot de passe</h5>
<p class="text-muted small">Laissez vide pour conserver le mot de passe actuel.</p>

<div class="mb-3">
<label class="form-label">Mot de passe actuel</label>
<input type="password" name="current_password" class="form-control form-control-lg">
</div>

<div class="mb-3">
<label class="form-label">Nouveau mot de passe</label>
<input type="password" name="new_password" class="form-control form-control-lg">
</div>

<div class="mb-4">
<label class="form-label">Confirmer le nouveau mot de passe</label>
<input type="password" name="new_password_confirmation" class="form-control form-control-lg">
</div>

<button type="submit" class="btn btn-primary px-5">Enregistrer</button>
</form>

</div>
</div>
</div>
</div>
</div>
@endsection

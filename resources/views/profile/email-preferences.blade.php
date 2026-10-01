@extends('layouts.app')

@section('content')
<div class="container py-4">
@include('profile.nav')

<h2 class="mb-4">Préférences e-mail</h2>

<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card border-0 shadow-sm" style="border-radius: 18px;">
<div class="card-body p-4">

@if(! $user->email)
<div class="alert alert-info py-2">
Ajoutez une adresse e-mail dans <a href="{{ route('profile.edit') }}">votre profil</a> pour recevoir des e-mails.
</div>
@elseif(! $user->hasVerifiedEmail())
<div class="alert alert-warning py-2">
Les e-mails sont envoyés uniquement à une adresse confirmée. Confirmez <strong>{{ $user->email }}</strong> grâce au lien reçu par e-mail.
</div>
@endif

<p class="text-muted">Choisissez les e-mails que vous souhaitez recevoir. Les notifications restent toujours visibles sur le site.</p>

<form method="POST" action="{{ route('profile.email-preferences.update') }}">
@csrf
@foreach($categories as $category)
<div class="form-check form-switch py-2 border-bottom">
<input class="form-check-input" type="checkbox" role="switch" name="categories[]" value="{{ $category->value }}" id="cat-{{ $category->value }}" @checked(! in_array($category->value, $user->email_preferences['disabled'] ?? [], true))>
<label class="form-check-label ms-2" for="cat-{{ $category->value }}">
<strong>{{ $category->label() }}</strong><br>
<small class="text-muted">{{ $category->description() }}</small>
</label>
</div>
@endforeach

<button type="submit" class="btn btn-primary mt-4 px-4" style="border-radius: 25px;">Enregistrer</button>
</form>

</div>
</div>
</div>
</div>
</div>
@endsection

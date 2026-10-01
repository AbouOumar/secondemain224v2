@extends('layouts.app')

@section('content')
<div class="container py-5">
<div class="row justify-content-center">
<div class="col-md-7 col-lg-5">
<div class="card border-0 shadow-sm text-center" style="border-radius: 18px;">
<div class="card-body p-5">
@if($done)
<i class="bx bx-check-circle" style="font-size: 3.5rem; color: var(--primary);"></i>
<h4 class="fw-bold mt-3">C'est noté</h4>
<p class="text-muted">Vous ne recevrez plus les e-mails « {{ $category->label() }} » à l'adresse {{ $user->email }}.</p>
@else
<i class="bx bx-envelope" style="font-size: 3.5rem; color: var(--primary);"></i>
<h4 class="fw-bold mt-3">Se désabonner</h4>
<p class="text-muted">Ne plus recevoir les e-mails « {{ $category->label() }} » à l'adresse {{ $user->email }} ?</p>
<form method="POST" action="{{ request()->fullUrl() }}">
@csrf
<button type="submit" class="btn btn-primary px-4" style="border-radius: 25px;">Confirmer la désinscription</button>
</form>
@endif
<p class="small text-muted mt-4 mb-0">Vous pouvez tout réactiver depuis <a href="{{ route('profile.email-preferences') }}">vos préférences e-mail</a>.</p>
</div>
</div>
</div>
</div>
</div>
@endsection

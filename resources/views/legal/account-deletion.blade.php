@extends('layouts.app')

@section('content')
<div class="container py-5">
<div class="row justify-content-center">
<div class="col-lg-9">
<h1 class="fw-bold mb-2">Supprimer mon compte</h1>
<p class="text-muted mb-4">{{ config('app.name') }} · Dernière mise à jour : {{ config('legal.updated_at') }}</p>

<div class="card border-0 shadow-sm p-4 mb-4 legal" style="border-radius: 18px;">
@include('pages.account-deletion')
</div>
</div>
</div>
</div>
@endsection

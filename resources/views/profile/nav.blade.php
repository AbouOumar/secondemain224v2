@php
    $currentRoute = Route::currentRouteName();
@endphp
<div class="d-flex gap-2 mb-4 flex-wrap">
    <a href="{{ route('profile.dashboard') }}" class="btn {{ str_starts_with($currentRoute, 'profile.dashboard') ? 'btn-primary' : 'btn-outline-secondary' }} px-4">
        <i class="bx bx-dashboard"></i> Tableau de bord
    </a>
    <a href="{{ route('profile.listings') }}" class="btn {{ str_starts_with($currentRoute, 'profile.listings') ? 'btn-primary' : 'btn-outline-secondary' }} px-4">
        <i class="bx bx-list-ul"></i> Mes annonces
    </a>
    <a href="{{ route('profile.saved') }}" class="btn {{ str_starts_with($currentRoute, 'profile.saved') ? 'btn-primary' : 'btn-outline-secondary' }} px-4">
        <i class="bx bx-bookmark"></i> Mes favoris
    </a>
    <a href="{{ route('profile.edit') }}" class="btn {{ str_starts_with($currentRoute, 'profile.edit') ? 'btn-primary' : 'btn-outline-secondary' }} px-4">
        <i class="bx bx-user"></i> Mon profil
    </a>
</div>

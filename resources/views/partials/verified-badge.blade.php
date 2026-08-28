{{-- Badge "vendeur vérifié". Usage : @include('partials.verified-badge', ['user' => $article->user]) --}}
@if($user->is_verified)
<span class="badge bg-warning text-dark" title="Identité vérifiée{{ $user->verified_at ? ' le '.$user->verified_at->format('d/m/Y') : '' }}">
    <i class='bx bxs-badge-check'></i> Vendeur vérifié
</span>
@endif

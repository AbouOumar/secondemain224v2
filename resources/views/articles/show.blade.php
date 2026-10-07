@extends('layouts.app')

@section('title', $article->titre.' — '.number_format($article->prix, 0, ',', ' ').' GNF | '.config('app.name'))
@section('meta_description', \Illuminate\Support\Str::limit(trim(number_format($article->prix, 0, ',', ' ').' GNF'.($article->localisation ? ' · '.$article->localisation : '').' · '.preg_replace('/\s+/', ' ', strip_tags((string) $article->description))), 200))
@section('og_type', 'product')
@if($article->images->isNotEmpty())
@section('og_image', $article->images->sortBy('ordre')->first()->url)
@endif
@push('meta')
<meta property="product:price:amount" content="{{ $article->prix }}">
<meta property="product:price:currency" content="{{ $article->currency ?? 'GNF' }}">
@endpush

@section('content')
<style>
.article-detail-container {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 2rem;
  align-items: start;
}
@media (max-width: 991px) {
  .article-detail-container {
    grid-template-columns: 1fr;
  }
}
.article-sidebar-wrapper {
  height: fit-content;
}
.article-sidebar {
  position: sticky;
  top: 72px;
  z-index: 50;
  max-height: calc(100vh - 100px);
  overflow-y: auto;
}
#articleCarousel .carousel-inner {
  overflow: hidden;
}
#articleCarousel .carousel-item img {
  transition: transform 0.25s ease-out;
  cursor: zoom-in;
  will-change: transform;
}
@media (prefers-reduced-motion: reduce) {
  #articleCarousel .carousel-item img {
    transition: none;
  }
}
</style>
<div class="container py-4">
<div class="article-detail-container">
<div class="col-lg-7" style="width: 100%;">
@if($article->images->count() > 0)
<div id="articleCarousel" class="carousel slide" data-bs-ride="carousel">
<div class="carousel-inner rounded-4 shadow-sm">
@foreach($article->images as $k => $img)
<div class="carousel-item {{ $k === 0 ? 'active' : '' }}">
<img src="{{ $img->url }}" class="d-block w-100" alt="{{ $article->titre }}" style="height: 400px; object-fit: cover;">
</div>
@endforeach
</div>
@if($article->images->count() > 1)
<button class="carousel-control-prev" type="button" data-bs-target="#articleCarousel" data-bs-slide="prev">
<span class="carousel-control-prev-icon"></span>
</button>
<button class="carousel-control-next" type="button" data-bs-target="#articleCarousel" data-bs-slide="next">
<span class="carousel-control-next-icon"></span>
</button>
<div class="carousel-indicators position-static mt-2">
@foreach($article->images as $k => $img)
<button type="button" data-bs-target="#articleCarousel" data-bs-slide-to="{{ $k }}" class="{{ $k === 0 ? 'active' : '' }}" aria-label="Image {{ $k + 1 }}"></button>
@endforeach
</div>
@endif
</div>
@if($article->images->count() > 1)
<div class="d-flex gap-2 mt-2 overflow-auto" style="scrollbar-width:thin;">
@foreach($article->images as $k => $img)
<img src="{{ $img->thumb_url }}" class="rounded border {{ $k === 0 ? 'border-primary' : 'border-secondary' }}" style="width:80px;height:60px;object-fit:cover;cursor:pointer;flex-shrink:0;" onclick="document.querySelector('#articleCarousel [data-bs-slide-to=\'{{ $k }}\']')?.click()" alt="">
@endforeach
</div>
@endif
@else
<div class="d-flex align-items-center justify-content-center bg-light rounded-4 shadow-sm" style="height: 400px;">
<i class="bx bx-image" style="font-size: 4rem; color: #94a3b8;"></i>
</div>
@endif

<div class="mt-4">
<h2 class="fw-bold">{{ $article->titre }}</h2>
<p class="text-muted">{{ $article->description }}</p>

<div class="d-flex flex-wrap gap-3 mt-3">
@if($article->category)
<span class="badge bg-light text-dark px-3 py-2"><i class="bx bx-tag"></i> {{ $article->category->libelle }}</span>
@endif
<span class="badge bg-light text-dark px-3 py-2"><i class="bx bx-check-shield"></i> {{ $article->etat }}</span>
@if($article->annee)
<span class="badge bg-light text-dark px-3 py-2"><i class="bx bx-calendar"></i> {{ $article->annee }}</span>
@endif
@if($article->localisation)
<span class="badge bg-light text-dark px-3 py-2"><i class="bx bx-map"></i> {{ $article->localisation }}</span>
@endif
</div>

@if(!empty($article->colors))
<div class="d-flex align-items-center mt-3 gap-1 flex-wrap">
<span class="fw-medium me-2">Couleurs :</span>
@foreach($article->colors as $hex)
<span class="color-dot" title="{{ \App\Enums\ProductColor::labelOf($hex) }}" style="width:22px;height:22px;border-radius:50%;background:{{ $hex }};border:2px solid #dee2e6;display:inline-block;margin-right:4px;"></span>
@endforeach
</div>
@endif

<div class="d-flex gap-2 mt-4">
@if($article->with_delivery)
<form method="POST" action="{{ route('orders.create', ['article' => $article->id, 'delivery' => 1]) }}" class="flex-grow-1">
@csrf
<button type="submit" class="btn btn-primary btn-lg w-100"><i class="bx bx-package"></i> Acheter avec livraison</button>
</form>
@endif
<form method="POST" action="{{ route('orders.create', ['article' => $article->id, 'delivery' => 0]) }}" class="flex-grow-1">
@csrf
<button type="submit" class="btn {{ $article->with_delivery ? 'btn-outline-primary' : 'btn-primary' }} btn-lg w-100"><i class="bx bx-cart"></i> {{ $article->with_delivery ? 'Acheter sans livraison' : 'Acheter' }}</button>
</form>
</div>

@auth
@if(auth()->id() !== $article->user_id && $article->statut !== 'vendu')
<button type="button" class="btn btn-outline-warning w-100 mt-2" data-bs-toggle="modal" data-bs-target="#make-offer-modal">
<i class="bx bx-purchase-tag"></i> Faire une offre
</button>
@endif
@endauth

<div class="d-flex gap-2 mt-3 position-relative">
<button type="button" class="btn btn-outline-secondary" id="saveBtn" data-saved="{{ in_array($article->id, $savedIds ?? []) ? '1' : '0' }}" onclick="toggleFavorite({{ $article->id }}, this)">
<i class="{{ in_array($article->id, $savedIds ?? []) ? 'bx bxs-heart' : 'bx bx-heart' }}"></i>
<span>{{ in_array($article->id, $savedIds ?? []) ? 'Enregistré' : 'Enregistrer' }}</span>
</button>
<a href="{{ route('messages.show', ['user' => $article->user_id, 'article' => $article->id]) }}" class="btn btn-outline-primary"><i class="bx bx-message-dots"></i> Contacter</a>
<button class="btn btn-outline-secondary share-btn" data-url="{{ request()->url() }}"><i class="bx bx-share-alt"></i> Partager</button>
<div class="share-popup shadow-sm">
<button class="btn btn-sm w-100 text-start" onclick="copyLink('{{ request()->url() }}')"><i class='bx bx-link-alt'></i> Copier</button>
<a class="btn btn-sm w-100 text-start" target="_blank" href="https://wa.me/?text={{ urlencode($article->titre . ' - ' . request()->url()) }}"><i class='bx bxl-whatsapp'></i> WhatsApp</a>
<a class="btn btn-sm w-100 text-start" target="_blank" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}"><i class='bx bxl-facebook'></i> Facebook</a>
<a class="btn btn-sm w-100 text-start" target="_blank" href="https://twitter.com/intent/tweet?url={{ urlencode(request()->url()) }}&text={{ urlencode($article->titre) }}"><i class='bx bxl-twitter'></i> X</a>
</div>
</div>
</div>
</div>

<div class="col-lg-5 article-sidebar-wrapper" style="width: 100%;">
<div class="card border-0 shadow-sm article-sidebar" style="border-radius: 18px;">
<div class="card-body p-4">
<div class="d-flex justify-content-between align-items-center mb-3">
<h3 class="fw-bold mb-0">{{ number_format($article->prix, 0, ',', ' ') }} {{ $article->currency->value }}</h3>
@if($article->is_boosted)
<span class="badge bg-warning text-dark"><i class="bx bx-rocket"></i> Boosté</span>
@endif
@if($article->with_delivery)
<span class="badge bg-info text-white"><i class="bx bx-check"></i> Livraison disponible</span>
@endif
@if(isset($article->stock) && $article->stock !== null && $article->stock > 0)
<span class="badge bg-success text-white"><i class="bx bx-box"></i> En stock ({{ $article->stock }})</span>
@elseif(isset($article->stock) && $article->stock !== null)
<span class="badge bg-danger text-white"><i class='bx bxs-box'></i> Rupture de stock</span>
@endif
@include('partials.verified-badge', ['user' => $article->user])
</div>

<hr>

<h6 class="fw-bold"><i class="bx bx-user-circle"></i> Vendeur</h6>
<p class="mb-1 fw-medium"><a href="{{ route('seller.public', $article->user_id) }}" class="text-decoration-none text-dark">{{ $article->user->name ?? 'Anonyme' }}</a></p>
@if($article->user->role->value === 'revendeur_pro' && $article->user->partner)
<p class="mb-1"><a href="{{ route('magasin.show', $article->user->partner->slug) }}" class="text-decoration-none small"><i class='bx bx-store'></i> {{ $article->user->partner->nom_magasin }}</a></p>
@endif
@if($sellerStats)
<ul class="list-unstyled small text-muted mb-2 seller-stats">
@if($sellerStats['rating_count'] > 0)
<li><i class="bx bxs-star text-warning"></i> <strong class="text-dark">{{ number_format($sellerStats['rating_avg'], 1, ',', ' ') }}/5</strong> ({{ $sellerStats['rating_count'] }} avis)</li>
@endif
<li><i class="bx bx-package"></i> {{ $sellerStats['sales_count'] > 0 ? $sellerStats['sales_count'].' vente'.($sellerStats['sales_count'] > 1 ? 's' : '').' réussie'.($sellerStats['sales_count'] > 1 ? 's' : '') : 'Nouveau vendeur' }}</li>
@if($sellerStats['response_rate'] !== null)
<li><i class="bx bx-message-check"></i> Répond à {{ $sellerStats['response_rate'] }} % des messages</li>
@endif
@if($sellerStats['member_since'])
<li><i class="bx bx-calendar"></i> Membre depuis {{ $sellerStats['member_since']->translatedFormat('F Y') }}</li>
@endif
</ul>
@endif
@if($sellerPhoneVisible)
<p class="text-muted small mb-2"><i class="bx bx-phone"></i> {{ $article->user->phone }}</p>
<a href="tel:{{ $article->user->phone }}" class="btn btn-outline-success btn-sm w-100 mb-3"><i class="bx bx-phone-call"></i> Appeler</a>
@else
<p class="text-muted small mb-3"><i class="bx bx-message-dots"></i> Échangez avec le vendeur via le bouton « Contacter ». Son numéro vous sera communiqué après votre commande.</p>
@endif

@auth
@if(auth()->id() !== $article->user_id)
<button class="btn btn-link btn-sm text-muted p-0 mb-3" data-bs-toggle="modal" data-bs-target="#report-article-{{ $article->id }}"><i class="bx bx-flag"></i> Signaler cette annonce</button>
@endif
@endauth

<div class="alert alert-light border small mb-3 py-2">
<i class='bx bx-lock-alt text-success'></i> Paiement sécurisé : les fonds sont bloqués jusqu'à confirmation de réception de l'article.
</div>

<hr>

<h6 class="fw-bold">Moyens de paiement</h6>
<div class="d-flex gap-2 flex-wrap">
<span class="badge bg-light text-dark px-3 py-2"><i class="bx bx-credit-card"></i> Orange Money</span>
<span class="badge bg-light text-dark px-3 py-2"><i class="bx bx-credit-card"></i> MTN Mobile Money</span>
<span class="badge bg-light text-dark px-3 py-2"><i class="bx bx-credit-card"></i> Yup</span>
</div>

@if($article->with_delivery && $article->delivery_prix > 0)
<hr>
<h6 class="fw-bold">Livraison</h6>
<p class="text-muted small mb-0">Prix livraison : {{ number_format($article->delivery_prix, 0, ',', ' ') }} GNF</p>
@endif
</div>
</div>

@if($relatedArticles->count() > 0)
<div class="mt-4">
<h5 class="fw-bold mb-3">Annonces similaires</h5>
<div class="related-scroll-wrapper">
	<div class="d-flex related-scroll gap-3" style="overflow-x:auto; padding-bottom:8px; scroll-snap-type:x mandatory; -webkit-overflow-scrolling:touch;">
		@foreach($relatedArticles as $rel)
		<div class="related-item" style="flex:0 0 240px; scroll-snap-align:start;">
			<div class="card article-card h-100 shadow-sm" style="min-width:220px;">
				@if($rel->images->count())
				<img src="{{ $rel->images->first()->thumb_url }}" class="card-img-top" alt="{{ $rel->titre }}" style="height:140px; object-fit:cover;">
				@else
				<img src="{{ asset('assets/img/icon.png') }}" class="card-img-top" alt="Pas d'image" style="height:140px; object-fit:contain; background:#f8f9fa;">
				@endif
				<div class="card-body p-2">
					<h6 class="title mb-1 text-truncate" style="font-size:0.95rem;">{{ $rel->titre }}</h6>
					<p class="text-muted small mb-2">{{ number_format($rel->prix,0,',',' ') }} {{ $rel->currency->value }}</p>
					<a href="{{ route('articles.show', $rel->slug) }}" class="stretched-link"></a>
				</div>
			</div>
		</div>
		@endforeach
	</div>
</div>
</div>
@endif
</div>
</div>
</div>

@auth
@if(auth()->id() !== $article->user_id)
@include('partials.report-modal', ['type' => 'article', 'id' => $article->id, 'label' => $article->titre])

<div class="modal fade" id="make-offer-modal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('offers.store', $article) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Faire une offre</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Prix affiché : {{ number_format($article->prix, 0, ',', ' ') }} {{ $article->currency->value }}. Le vendeur pourra accepter, refuser ou vous faire une contre-offre.</p>
                <label class="form-label small fw-medium">Votre offre (GNF) *</label>
                <input type="number" name="montant" class="form-control" min="{{ (int) round($article->prix * 0.5) }}" required>
                <small class="text-muted">Minimum {{ number_format(round($article->prix * 0.5), 0, ',', ' ') }} GNF.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-warning"><i class="bx bx-purchase-tag"></i> Envoyer l'offre</button>
            </div>
        </form>
    </div>
</div>
@endif
@endauth
@endsection

@push('scripts')
<script>
const articleId = {{ $article->id }};

document.addEventListener('click', e => {
document.querySelectorAll('.share-popup').forEach(sp => sp.style.display = 'none');
const shareBtn = e.target.closest('.share-btn');
if (shareBtn) {
const popup = shareBtn.parentNode.querySelector('.share-popup');
if (popup) popup.style.display = 'block';
}
});
</script>
<script>
// Zoom photo qui suit le pointeur : au survol de la photo principale, on
// zoome au niveau exact du curseur (comme sur les fiches produit e-commerce).
(function () {
    var images = document.querySelectorAll('#articleCarousel .carousel-item img');
    images.forEach(function (img) {
        img.addEventListener('mousemove', function (e) {
            var rect = img.getBoundingClientRect();
            var x = ((e.clientX - rect.left) / rect.width) * 100;
            var y = ((e.clientY - rect.top) / rect.height) * 100;
            img.style.transformOrigin = x + '% ' + y + '%';
            img.style.transform = 'scale(1.6)';
        });
        img.addEventListener('mouseleave', function () {
            img.style.transform = 'scale(1)';
            img.style.transformOrigin = 'center center';
        });
    });
})();
</script>
@endpush

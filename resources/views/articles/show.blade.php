@extends('layouts.app')

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
</style>
<div class="container py-4">
<div class="article-detail-container">
<div class="col-lg-7" style="width: 100%;">
@if($article->images->count() > 0)
<div id="articleCarousel" class="carousel slide" data-bs-ride="carousel">
<div class="carousel-inner rounded-4 shadow-sm">
@foreach($article->images as $k => $img)
<div class="carousel-item {{ $k === 0 ? 'active' : '' }}">
<img src="{{ $img->url }}?fit=fill&w=800&h=500" class="d-block w-100" alt="{{ $article->titre }}" style="height: 400px; object-fit: cover;">
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
<img src="{{ $img->url }}?fit=fill&w=100&h=80" class="rounded border {{ $k === 0 ? 'border-primary' : 'border-secondary' }}" style="width:80px;height:60px;object-fit:cover;cursor:pointer;flex-shrink:0;" onclick="document.querySelector('#articleCarousel [data-bs-slide-to=\'{{ $k }}\']')?.click()" alt="">
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

<div class="d-flex gap-2 mt-4">
<form method="POST" action="{{ route('orders.create', ['article' => $article->id, 'delivery' => 1]) }}" class="flex-grow-1">
@csrf
<button type="submit" class="btn btn-primary btn-lg w-100"><i class="bx bx-package"></i> Acheter avec livraison</button>
</form>
<form method="POST" action="{{ route('orders.create', ['article' => $article->id, 'delivery' => 0]) }}" class="flex-grow-1">
@csrf
<button type="submit" class="btn btn-outline-primary btn-lg w-100"><i class="bx bx-cart"></i> Acheter sans livraison</button>
</form>
</div>

<div class="d-flex gap-2 mt-3">
<button class="btn btn-outline-secondary" id="saveBtn" onclick="toggleSave()"><i class="bx bx-bookmark"></i> Enregistrer</button>
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
@if($article->user->role->value === 'revendeur_pro' && $article->stock > 0)
<span class="badge bg-success text-white"><i class="bx bx-box"></i> En stock ({{ $article->stock }})</span>
@elseif($article->user->role->value === 'revendeur_pro')
<span class="badge bg-danger text-white"><i class='bx bxs-box'></i> Rupture de stock</span>
@endif
@if($article->user->is_verified)
<span class="badge bg-warning text-dark"><i class='bx bx-shield'></i> Vérifié</span>
@endif
</div>

<hr>

<h6 class="fw-bold"><i class="bx bx-user-circle"></i> Vendeur</h6>
<p class="mb-1 fw-medium">{{ $article->user->name ?? 'Anonyme' }}</p>
@if($article->user->role->value === 'revendeur_pro' && $article->user->partner)
<p class="mb-1"><a href="{{ route('magasin.show', $article->user->partner->slug) }}" class="text-decoration-none small"><i class='bx bx-store'></i> {{ $article->user->partner->nom_magasin }}</a></p>
@endif
@if($article->user->phone)
<p class="text-muted small mb-2"><i class="bx bx-phone"></i> {{ $article->user->phone }}</p>
@endif
<a href="tel:{{ $article->user->phone ?? '' }}" class="btn btn-outline-success btn-sm w-100 mb-3"><i class="bx bx-phone-call"></i> Appeler</a>

<hr>

<h6 class="fw-bold">Moyens de paiement</h6>
<div class="d-flex gap-2 flex-wrap">
<span class="badge bg-light text-dark px-3 py-2"><i class="bx bx-credit-card"></i> Orange Money</span>
<span class="badge bg-light text-dark px-3 py-2"><i class="bx bx-credit-card"></i> MTN Mobile Money</span>
<span class="badge bg-light text-dark px-3 py-2"><i class="bx bx-credit-card"></i> Yup</span>
</div>

@if($article->with_delivery)
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
			<div class="card h-100 shadow-sm" style="min-width:220px;">
				@if($rel->images->count())
				<img src="{{ $rel->images->first()->url }}?fit=fill&w=300&h=180" class="card-img-top" alt="{{ $rel->titre }}" style="height:140px; object-fit:cover;">
				@else
				<img src="{{ asset('assets/img/icon.png') }}" class="card-img-top" alt="Pas d'image" style="height:140px; object-fit:contain; background:#f8f9fa;">
				@endif
				<div class="card-body p-2">
					<h6 class="mb-1 text-truncate" style="font-size:0.95rem;">{{ $rel->titre }}</h6>
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
@endsection

@push('scripts')
<script>
const articleId = {{ $article->id }};
const saveUrl = '{{ route("saved.toggle", $article->id) }}';
const saveStatusUrl = '{{ route("saved.toggle", $article->id) }}?check=1';

function toggleSave() {
    fetch(saveUrl, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
        .then(r => r.json())
        .then(d => updateBtn(d.saved))
        .catch(() => {});
}

function updateBtn(saved) {
    const btn = document.getElementById('saveBtn');
    if (saved) {
        btn.innerHTML = '<i class="bx bx-bookmark"></i> Enregistré';
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-warning');
    } else {
        btn.innerHTML = '<i class="bx bx-bookmark"></i> Enregistrer';
        btn.classList.remove('btn-warning');
        btn.classList.add('btn-outline-secondary');
    }
}

fetch(saveStatusUrl).then(r => r.json()).then(d => updateBtn(d.saved)).catch(() => {});

document.addEventListener('click', e => {
document.querySelectorAll('.share-popup').forEach(sp => sp.style.display = 'none');
if (e.target.classList.contains('share-btn')) {
const popup = e.target.parentNode.querySelector('.share-popup');
if (popup) popup.style.display = 'block';
}
});
</script>
@endpush

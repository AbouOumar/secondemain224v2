@forelse($articles as $item)
<div class="col-12 col-sm-6 col-md-4 col-lg-3 article-item">
<div class="card article-card h-100 border-0 shadow-sm position-relative w-100 d-flex flex-column">
<div class="position-relative">
<img src="{{ $item->images->first()->url ?? 'https://placehold.co/300x200/e2e8f0/94a3b8?text=Photo' }}?fit=fill&w=300&h=200" alt="{{ $item->titre }}" class="card-img-top" loading="lazy">
<span class="badge price-badge">{{ number_format($item->prix, 0, ',', ' ') }} {{ $item->currency->value }}</span>
</div>
<div class="card-body d-flex flex-column p-3 flex-grow-1">
<h6 class="title mb-1 text-truncate" title="{{ $item->titre }}">{{ $item->titre }}</h6>
<p class="text-muted small mb-1 flex-grow-1">{{ Str::limit($item->description, 80) }}</p>
<span class="small text-muted mb-2">{{ $item->category->libelle ?? '' }}</span>
@if($item->user->role->value === 'revendeur_pro' && $item->user->partner)
<div class="mb-2"><a href="{{ route('magasin.show', $item->user->partner->slug) }}" class="small text-decoration-none"><i class='bx bx-store'></i> {{ $item->user->partner->nom_magasin }}</a></div>
@endif
<div class="d-flex gap-2 position-relative mt-auto">
<a href="{{ route('articles.show', $item->slug) }}" class="btn btn-sm btn-primary w-100">Voir</a>
<button type="button" class="btn btn-sm btn-outline-secondary share-btn" data-url="{{ route('articles.show', $item->slug) }}"><i class='bx bx-share-alt'></i></button>
<div class="share-popup shadow-sm">
<button class="btn btn-sm w-100 text-start" onclick="copyLink('{{ route('articles.show', $item->slug) }}')"><i class='bx bx-link-alt'></i> Copier</button>
<a class="btn btn-sm w-100 text-start" target="_blank" href="https://wa.me/?text={{ urlencode($item->titre.' - '.route('articles.show', $item->slug)) }}"><i class='bx bxl-whatsapp'></i> WhatsApp</a>
<a class="btn btn-sm w-100 text-start" target="_blank" href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('articles.show', $item->slug)) }}"><i class='bx bxl-facebook'></i> Facebook</a>
<a class="btn btn-sm w-100 text-start" target="_blank" href="https://twitter.com/intent/tweet?url={{ urlencode(route('articles.show', $item->slug)) }}&text={{ urlencode($item->titre) }}"><i class='bx bxl-twitter'></i> X</a>
</div>
</div>
</div>
</div>
</div>
@empty
<div class="col-12"><div class="alert alert-warning">Aucun article trouvé.</div></div>
@endforelse

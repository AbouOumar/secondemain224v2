@forelse($articles as $item)
<div class="col-12 col-sm-6 col-md-4 col-lg-3 article-item">
<div class="card article-card h-100 border-0 shadow-sm position-relative">
<div class="position-relative">
<img src="{{ $item->images->first()->url ?? 'https://placehold.co/300x200/e2e8f0/94a3b8?text=Photo' }}?fit=fill&w=300&h=200" alt="{{ $item->titre }}" class="card-img-top" loading="lazy">
<span class="badge price-badge">{{ number_format($item->prix, 0, ',', ' ') }} {{ $item->currency->value }}</span>
<span class="badge position-absolute top-0 end-0 m-2 bg-{{ $item->statut === 'vendu' ? 'danger' : ($item->is_boosted ? 'warning' : 'success') }}">{{ $item->statut === 'vendu' ? 'Vendu' : ($item->is_boosted ? 'Boosté' : 'En vente') }}</span>
@if($item->is_boosted)<span class="badge position-absolute top-0 start-0 m-2 bg-warning text-dark"><i class="bx bx-rocket"></i></span>@endif
</div>
<div class="card-body d-flex flex-column p-3">
<h6 class="title mb-1 text-truncate" title="{{ $item->titre }}">{{ $item->titre }}</h6>
<p class="text-muted small mb-1 flex-grow-1">{{ Str::limit($item->description, 80) }}</p>
<span class="small text-muted mb-2">{{ $item->category->libelle ?? '' }}</span>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('articles.edit', $item->id) }}" class="btn btn-sm btn-outline-primary"><i class="bx bx-edit"></i> Modifier</a>
                @if($item->statut !== 'vendu')
                <form method="POST" action="{{ route('articles.toggle-status', $item->id) }}" class="d-inline">
                    @csrf
                    @method('POST')
                    <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Marquer comme vendu ?')"><i class="bx bx-check"></i> Vendu</button>
                </form>
                @endif
                @if(!$item->is_boosted)
                <button type="button" class="btn btn-sm btn-outline-warning" data-url="{{ route('articles.boost', $item->id) }}" onclick="openBoostModal(this)"><i class="bx bx-rocket"></i> Booster</button>
                @else
                <span class="btn btn-sm btn-warning text-white disabled"><i class="bx bx-rocket"></i> Boosté</span>
                @endif
                <form method="POST" action="{{ route('articles.destroy', $item->id) }}" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cette annonce ?')"><i class="bx bx-trash"></i></button>
                </form>
            </div>
</div>
</div>
</div>
@empty
<div class="col-12"><div class="alert alert-warning">Aucun article trouvé.</div></div>
@endforelse

@extends('layouts.app')
@section('content')
<div class="container py-4">
    @include('profile.nav')

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Mes favoris</h2>
    </div>

    @if($articles->isEmpty())
        <div class="text-center py-5">
            <i class='bx bx-bookmark' style="font-size:4rem;color:var(--gray-400);"></i>
            <p class="text-muted mt-3">Vous n'avez encore aucun article en favori.</p>
            <a href="{{ url('/') }}" class="btn btn-primary">Parcourir les annonces</a>
        </div>
    @else
        <div class="row g-4">
            @foreach($articles as $item)
                <div class="col-12 col-sm-6 col-md-4 col-lg-3 article-item">
                    <div class="card article-card h-100 border-0 shadow-sm position-relative w-100 d-flex flex-column">
                        <div class="position-relative">
                            <a href="{{ route('articles.show', $item->slug) }}">
                                <img src="{{ $item->images->first()->url ?? 'https://placehold.co/300x200/e2e8f0/94a3b8?text=Photo' }}?fit=fill&w=300&h=200" class="card-img-top" loading="lazy" alt="{{ $item->titre }}">
                            </a>
                            <span class="badge price-badge">{{ number_format($item->prix, 0, ',', ' ') }} {{ $item->currency->value }}</span>
                        </div>
                        <div class="card-body d-flex flex-column p-3 flex-grow-1">
                            <h6 class="title mb-1 text-truncate" title="{{ $item->titre }}">{{ $item->titre }}</h6>
                            <p class="text-muted small mb-1 flex-grow-1">{{ Str::limit($item->description, 80) }}</p>
                            <span class="small text-muted mb-2">{{ $item->category->libelle ?? '' }}</span>
                            <div class="d-flex gap-2 mt-auto">
                                <a href="{{ route('articles.show', $item->slug) }}" class="btn btn-sm btn-primary w-100">Voir</a>
                                <button class="btn btn-sm btn-outline-danger" onclick="unsave({{ $item->id }}, this)"><i class="bx bx-bookmark-minus"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">
            {{ $articles->links() }}
        </div>
    @endif
</div>

@push('scripts')
<script>
function unsave(id, btn) {
    fetch('/saved/' + id, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
        .then(r => r.json())
        .then(d => { if (!d.saved) btn.closest('.article-item').remove(); });
}
</script>
@endpush
@endsection

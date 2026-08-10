@extends('admin.layout')

@section('title', 'Modération des annonces')

@section('content')
    <div class="admin-topbar">
        <h1 class="h4 mb-0">Modération des annonces</h1>
        <div class="btn-group btn-group-sm">
            <a href="{{ route('admin.articles.index') }}" class="btn btn-outline-secondary {{ request('filtre') !== 'rejetes' ? 'active' : '' }}">En attente</a>
            <a href="{{ route('admin.articles.index', ['filtre' => 'rejetes']) }}" class="btn btn-outline-secondary {{ request('filtre') === 'rejetes' ? 'active' : '' }}">Rejetées</a>
        </div>
    </div>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Annonce</th>
                        <th>Vendeur</th>
                        <th>Catégorie</th>
                        <th>Prix</th>
                        <th>Publiée le</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($articles as $article)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if($article->images->first())
                                        <img src="{{ asset('storage/'.$article->images->first()->url) }}" style="width:40px;height:40px;object-fit:cover;border-radius:6px;">
                                    @endif
                                    <span>{{ $article->titre }}</span>
                                </div>
                                @if($article->rejection_raison)
                                    <div class="small text-danger mt-1">Motif : {{ $article->rejection_raison }}</div>
                                @endif
                            </td>
                            <td class="small">{{ $article->user->name ?? '—' }}</td>
                            <td class="small text-muted">{{ $article->category->libelle ?? '—' }}</td>
                            <td class="small">{{ number_format($article->prix, 0, ',', ' ') }} {{ $article->currency->value ?? '' }}</td>
                            <td class="small text-muted">{{ $article->created_at->format('d/m/Y') }}</td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    @if(!$article->is_verified)
                                        <form method="POST" action="{{ route('admin.articles.verify', $article) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success">Valider</button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reject-{{ $article->id }}">Rejeter</button>
                                    @endif
                                    <a href="{{ url('/articles/'.$article->slug) }}" target="_blank" class="btn btn-sm btn-outline-secondary">Voir</a>
                                </div>
                            </td>
                        </tr>

                        <div class="modal fade" id="reject-{{ $article->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <form method="POST" action="{{ route('admin.articles.reject', $article) }}" class="modal-content">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">Rejeter « {{ $article->titre }} »</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <label class="form-label small">Motif du rejet</label>
                                        <textarea name="raison" class="form-control" rows="3" placeholder="Expliquez pourquoi cette annonce est rejetée..."></textarea>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" class="btn btn-danger">Rejeter l'annonce</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucune annonce à traiter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">{{ $articles->links() }}</div>
    </div>
@endsection

@extends('admin.layout')

@section('title', 'Catégories')

@section('content')
    <div class="admin-topbar">
        <h1 class="h4 mb-0">Catégories</h1>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#create-category">
            <i class='bx bx-plus'></i> Nouvelle catégorie
        </button>
    </div>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Sous-catégories</th>
                        <th>Annonces</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>
                                @if($category->icon)<i class='bx {{ $category->icon }} me-1'></i>@endif
                                {{ $category->libelle }}
                            </td>
                            <td class="small text-muted">
                                {{ $category->children->pluck('libelle')->join(', ') ?: '—' }}
                            </td>
                            <td class="small">{{ $category->articles()->count() }}</td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#edit-{{ $category->id }}">Modifier</button>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cette catégorie ?')">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <div class="modal fade" id="edit-{{ $category->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="modal-content">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Modifier « {{ $category->libelle }} »</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-2">
                                            <label class="form-label small">Nom</label>
                                            <input type="text" name="libelle" value="{{ $category->libelle }}" class="form-control" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">Icône (boxicons)</label>
                                            <input type="text" name="icon" value="{{ $category->icon }}" class="form-control" placeholder="bx-car">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">Catégorie parente</label>
                                            <select name="parent_id" class="form-select">
                                                <option value="">Aucune</option>
                                                @foreach($allCategories as $option)
                                                    @if($option->id !== $category->id)
                                                        <option value="{{ $option->id }}" {{ $category->parent_id === $option->id ? 'selected' : '' }}>{{ $option->libelle }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small">Description</label>
                                            <textarea name="description" class="form-control" rows="2">{{ $category->description }}</textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">Aucune catégorie.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="create-category" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.categories.store') }}" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nouvelle catégorie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small">Nom</label>
                        <input type="text" name="libelle" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Icône (boxicons)</label>
                        <input type="text" name="icon" class="form-control" placeholder="bx-car">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Catégorie parente</label>
                        <select name="parent_id" class="form-select">
                            <option value="">Aucune</option>
                            @foreach($allCategories as $option)
                                <option value="{{ $option->id }}">{{ $option->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer</button>
                </div>
            </form>
        </div>
    </div>
@endsection

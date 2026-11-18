@extends('admin.layout')

@section('title', 'Magasins pro')

@section('content')
    <div class="admin-topbar">
        <h1 class="h4 mb-0">Magasins pro</h1>
        <div class="btn-group btn-group-sm">
            <a href="{{ route('admin.partners.index') }}" class="btn btn-outline-secondary {{ !request()->has('is_verified') ? 'active' : '' }}">Tous</a>
            <a href="{{ route('admin.partners.index', ['is_verified' => 0]) }}" class="btn btn-outline-secondary {{ request('is_verified') === '0' ? 'active' : '' }}">Non vérifiés</a>
            <a href="{{ route('admin.partners.index', ['is_verified' => 1]) }}" class="btn btn-outline-secondary {{ request('is_verified') === '1' ? 'active' : '' }}">Vérifiés</a>
        </div>
    </div>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Magasin</th>
                        <th>Propriétaire</th>
                        <th>Téléphone</th>
                        <th>Statut</th>
                        <th title="Appliquée aux magasins vérifiés ; les autres paient le taux des particuliers">Commission</th>
                        <th>Créé le</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($partners as $partner)
                        <tr>
                            <td>
                                <a href="{{ url('/boutique/'.$partner->slug) }}" target="_blank">{{ $partner->nom_magasin }}</a>
                            </td>
                            <td class="small">{{ $partner->user->name ?? '—' }}</td>
                            <td class="small text-muted">{{ $partner->telephone ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $partner->is_verified ? 'badge-soft-success' : 'badge-soft-warning' }}">
                                    {{ $partner->is_verified ? 'Vérifié' : 'En attente' }}
                                </span>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.partners.commission', $partner) }}" class="d-flex gap-1 align-items-center" style="min-width: 150px;">
                                    @csrf
                                    <input type="number" name="rate" step="0.5" min="{{ config('marketplace.partner_commission_min') * 100 }}" max="{{ config('marketplace.partner_commission_max') * 100 }}"
                                           value="{{ $partner->commission_rate !== null ? $partner->commission_rate * 100 : '' }}"
                                           placeholder="{{ config('marketplace.partner_commission_rate') * 100 }}" class="form-control form-control-sm" style="width: 70px;">
                                    <span class="small">%</span>
                                    <button class="btn btn-sm btn-outline-primary">OK</button>
                                </form>
                            </td>
                            <td class="small text-muted">{{ $partner->created_at->format('d/m/Y') }}</td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    @if($partner->is_verified)
                                        <form method="POST" action="{{ route('admin.partners.unverify', $partner) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-warning">Retirer la vérification</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.partners.verify', $partner) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-success">Vérifier</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('admin.partners.destroy', $partner) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ce magasin ?')">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">Aucun magasin.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">{{ $partners->links() }}</div>
    </div>
@endsection

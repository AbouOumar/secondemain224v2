@extends('admin.layout')

@section('title', 'Demandes de rôle')

@section('content')
    <div class="admin-topbar">
        <h1 class="h4 mb-0">Demandes livreur / revendeur pro</h1>
        <div class="btn-group btn-group-sm">
            @foreach (['en_attente' => 'En attente', 'approuve' => 'Acceptées', 'refuse' => 'Refusées', 'tous' => 'Toutes'] as $key => $label)
                <a href="{{ route('admin.role-requests.index', ['status' => $key]) }}" class="btn btn-outline-secondary {{ $status === $key ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Candidat</th>
                        <th>Rôle demandé</th>
                        <th>Informations</th>
                        <th>Identité</th>
                        <th style="min-width: 260px;">Décision</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $req)
                        @php
                            $details = $req->details ?? [];
                            $identity = $req->user->latestIdentityVerification?->status;
                        @endphp
                        <tr>
                            <td>
                                {{ $req->user->name }}
                                <div class="small text-muted">{{ $req->user->phone }}{{ $req->user->email ? ' · ' . $req->user->email : '' }}</div>
                                <div class="small text-muted">{{ $req->created_at->format('d/m/Y H:i') }}</div>
                            </td>
                            <td><span class="badge bg-primary">{{ $req->role === 'motard' ? 'Livreur (motard)' : 'Revendeur pro' }}</span></td>
                            <td class="small">
                                @if ($req->role === 'motard')
                                    @forelse (['vehicle' => 'Véhicule', 'plate' => 'Plaque', 'zone' => 'Zone'] as $key => $label)
                                        @if (! empty($details[$key]))<div>{{ $label }} : {{ $details[$key] }}</div>@endif
                                    @empty
                                    @endforelse
                                @else
                                    @foreach (['business_name' => 'Commerce', 'address' => 'Adresse', 'registration_number' => 'N° RCCM'] as $key => $label)
                                        @if (! empty($details[$key]))<div>{{ $label }} : {{ $details[$key] }}</div>@endif
                                    @endforeach
                                @endif
                                @if (empty($details))<span class="text-muted">Détails non fournis (demande faite à l'inscription)</span>@endif
                            </td>
                            <td>
                                @if ($identity === 'approved')<span class="badge bg-success">Vérifiée</span>
                                @elseif ($identity === 'pending')<span class="badge bg-warning text-dark">En cours</span>
                                @elseif ($identity === 'rejected')<span class="badge bg-secondary">Refusée</span>
                                @else<span class="badge bg-light text-dark border">Aucune pièce</span>@endif
                            </td>
                            <td>
                                @if ($req->status === 'en_attente')
                                    <form method="POST" action="{{ route('admin.role-requests.approve', $req) }}" class="mb-1">
                                        @csrf
                                        <button class="btn btn-success btn-sm" onclick="return confirm('Accorder ce rôle ?')">Accepter</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.role-requests.reject', $req) }}" class="d-flex gap-1">
                                        @csrf
                                        <input type="text" name="note" class="form-control form-control-sm" placeholder="Motif du refus">
                                        <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Refuser cette demande ?')">Refuser</button>
                                    </form>
                                @else
                                    <span class="badge {{ $req->status === 'approuve' ? 'bg-success' : 'bg-secondary' }}">{{ $req->status === 'approuve' ? 'Acceptée' : 'Refusée' }}</span>
                                    <div class="small text-muted">{{ $req->processed_at?->format('d/m/Y H:i') }}{{ $req->processor ? ' · ' . $req->processor->name : '' }}</div>
                                    @if ($req->note)<div class="small text-muted">{{ $req->note }}</div>@endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Aucune demande.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $requests->links() }}</div>
    </div>
@endsection

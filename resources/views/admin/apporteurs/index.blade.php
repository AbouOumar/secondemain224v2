@extends('admin.layout')

@section('title', 'Apporteurs d\'affaires')

@section('content')
    <div class="admin-topbar">
        <h1 class="h4 mb-0">Apporteurs d'affaires</h1>
        <div class="d-flex gap-2 flex-wrap">
            <form method="GET" action="{{ route('admin.apporteurs.index') }}" class="d-flex gap-1">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Code, nom ou téléphone">
                <button class="btn btn-sm btn-outline-secondary">Chercher</button>
            </form>
            <div class="btn-group btn-group-sm">
                @foreach (['actif' => 'Actifs', 'suspendu' => 'Suspendus', 'tous' => 'Tous'] as $key => $label)
                    <a href="{{ route('admin.apporteurs.index', ['status' => $key]) }}" class="btn btn-outline-secondary {{ $status === $key ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    </div>

    <p class="small text-muted">Chaque utilisateur obtient son code de parrainage sans validation, en ouvrant « Apporteur d'affaires ». Suspendez un code en cas d'abus : il n'est plus accepté et ses gains ne sont plus versés.</p>

    <div class="row g-3 mb-3">
        @foreach ([['Classement de la semaine', 'Prime de 100 000 GNF au premier', $week], ['Classement du mois', 'Challenge du mois', $month]] as [$title, $subtitle, $board])
            <div class="col-lg-6">
                <div class="card p-3 h-100">
                    <h2 class="h6 mb-0">{{ $title }}</h2>
                    <div class="small text-muted mb-2">{{ $subtitle }} · personnes parrainées ayant publié au moins une annonce</div>
                    @forelse ($board as $i => $row)
                        <div class="d-flex justify-content-between border-bottom py-1 small">
                            <span><strong>{{ $i + 1 }}.</strong> {{ $row['apporteur']->user->name }} <span class="text-muted">({{ $row['apporteur']->code }})</span></span>
                            <span>{{ $row['sellers'] }} vendeur{{ $row['sellers'] > 1 ? 's' : '' }} · {{ $row['first_sales'] }} vente{{ $row['first_sales'] > 1 ? 's' : '' }} · {{ number_format($row['earned'], 0, ',', ' ') }} GNF</span>
                        </div>
                    @empty
                        <div class="small text-muted">Aucune activité sur la période.</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <div class="card p-3">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Apporteur</th>
                        <th>Code</th>
                        <th>Parrainés</th>
                        <th>Gains versés</th>
                        <th style="min-width: 260px;">Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($apporteurs as $a)
                        <tr>
                            <td>
                                {{ $a->user->name }}
                                <div class="small text-muted">{{ $a->user->phone }}{{ $a->user->email ? ' · ' . $a->user->email : '' }}</div>
                                <div class="small text-muted">Code créé le {{ $a->created_at->format('d/m/Y') }}</div>
                            </td>
                            <td>{!! $a->code ? '<code class="fs-6">' . e($a->code) . '</code>' : '<span class="text-muted">—</span>' !!}</td>
                            <td>{{ $a->referrals_count }}</td>
                            <td>{{ number_format((int) $a->earned, 0, ',', ' ') }} GNF</td>
                            <td>
                                @if ($a->status === 'actif')
                                    <span class="badge bg-success mb-1">Actif</span>
                                    <form method="POST" action="{{ route('admin.apporteurs.suspend', $a) }}" class="d-flex gap-1">
                                        @csrf
                                        <input type="text" name="note" class="form-control form-control-sm" placeholder="Motif">
                                        <button class="btn btn-outline-warning btn-sm" onclick="return confirm('Suspendre ce code ? Il ne sera plus accepté.')">Suspendre</button>
                                    </form>
                                @elseif ($a->status === 'suspendu')
                                    <span class="badge bg-warning text-dark">Suspendu</span>
                                    @if ($a->note)<div class="small text-muted">{{ $a->note }}</div>@endif
                                    <div class="small text-muted">{{ $a->processed_at?->format('d/m/Y H:i') }}{{ $a->processor ? ' · ' . $a->processor->name : '' }}</div>
                                    <form method="POST" action="{{ route('admin.apporteurs.reactivate', $a) }}" class="mt-1">
                                        @csrf
                                        <button class="btn btn-outline-success btn-sm">Réactiver</button>
                                    </form>
                                @else
                                    <span class="badge bg-secondary">{{ $a->statusLabel() }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Aucun apporteur.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $apporteurs->links() }}</div>
    </div>
@endsection

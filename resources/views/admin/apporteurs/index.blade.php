@extends('admin.layout')

@section('title', 'Apporteurs d\'affaires')

@section('content')
    <div class="admin-topbar">
        <h1 class="h4 mb-0">Apporteurs d'affaires</h1>
        <div class="btn-group btn-group-sm">
            @foreach (['en_attente' => 'Candidatures' . ($pendingCount ? " ({$pendingCount})" : ''), 'actif' => 'Actifs', 'suspendu' => 'Suspendus', 'refuse' => 'Refusés', 'tous' => 'Tous'] as $key => $label)
                <a href="{{ route('admin.apporteurs.index', ['status' => $key]) }}" class="btn btn-outline-secondary {{ $status === $key ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach ([['Classement de la semaine', 'Prime de 100 000 GNF au premier', $week], ['Classement du mois', 'Challenge du mois', $month]] as [$title, $subtitle, $board])
            <div class="col-lg-6">
                <div class="card p-3 h-100">
                    <h2 class="h6 mb-0">{{ $title }}</h2>
                    <div class="small text-muted mb-2">{{ $subtitle }} · vendeurs apportés ayant publié au moins une annonce</div>
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
                        <th>Candidature</th>
                        <th>Code</th>
                        <th>Vendeurs</th>
                        <th>Gains versés</th>
                        <th style="min-width: 260px;">Décision</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($apporteurs as $a)
                        @php($details = $a->details ?? [])
                        <tr>
                            <td>
                                {{ $a->user->name }}
                                <div class="small text-muted">{{ $a->user->phone }}{{ $a->user->email ? ' · ' . $a->user->email : '' }}</div>
                                <div class="small text-muted">Depuis le {{ $a->created_at->format('d/m/Y') }}</div>
                            </td>
                            <td class="small">
                                @foreach (['quartier' => 'Quartier', 'activite' => 'Activité', 'motivation' => 'Motivation'] as $key => $label)
                                    @if (! empty($details[$key]))<div>{{ $label }} : {{ $details[$key] }}</div>@endif
                                @endforeach
                            </td>
                            <td>{!! $a->code ? '<code class="fs-6">' . e($a->code) . '</code>' : '<span class="text-muted">—</span>' !!}</td>
                            <td>{{ $a->referrals_count }}</td>
                            <td>{{ number_format((int) $a->earned, 0, ',', ' ') }} GNF</td>
                            <td>
                                @if ($a->status === 'en_attente')
                                    <form method="POST" action="{{ route('admin.apporteurs.approve', $a) }}" class="mb-1">
                                        @csrf
                                        <button class="btn btn-success btn-sm" onclick="return confirm('Accepter et attribuer un code ?')">Accepter</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.apporteurs.reject', $a) }}" class="d-flex gap-1">
                                        @csrf
                                        <input type="text" name="note" class="form-control form-control-sm" placeholder="Motif du refus">
                                        <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Refuser cette candidature ?')">Refuser</button>
                                    </form>
                                @elseif ($a->status === 'actif')
                                    <span class="badge bg-success mb-1">Actif</span>
                                    <form method="POST" action="{{ route('admin.apporteurs.suspend', $a) }}" class="d-flex gap-1">
                                        @csrf
                                        <input type="text" name="note" class="form-control form-control-sm" placeholder="Motif">
                                        <button class="btn btn-outline-warning btn-sm" onclick="return confirm('Suspendre cet apporteur ? Son code ne sera plus accepté.')">Suspendre</button>
                                    </form>
                                @elseif ($a->status === 'suspendu')
                                    <span class="badge bg-warning text-dark">Suspendu</span>
                                    @if ($a->note)<div class="small text-muted">{{ $a->note }}</div>@endif
                                    <form method="POST" action="{{ route('admin.apporteurs.reactivate', $a) }}" class="mt-1">
                                        @csrf
                                        <button class="btn btn-outline-success btn-sm">Réactiver</button>
                                    </form>
                                @else
                                    <span class="badge bg-secondary">Refusé</span>
                                    <div class="small text-muted">{{ $a->processed_at?->format('d/m/Y H:i') }}{{ $a->processor ? ' · ' . $a->processor->name : '' }}</div>
                                    @if ($a->note)<div class="small text-muted">{{ $a->note }}</div>@endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">Aucun apporteur.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $apporteurs->links() }}</div>
    </div>
@endsection

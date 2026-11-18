<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Apporteur;
use App\Services\Apporteur\ApporteurService;
use Illuminate\Http\Request;

/** Candidatures, suivi et classement des apporteurs d'affaires. */
class ApporteurController extends Controller
{
    private const STATUSES = [Apporteur::PENDING, Apporteur::ACTIVE, Apporteur::SUSPENDED, Apporteur::REJECTED];

    public function __construct(private ApporteurService $apporteurs) {}

    public function index(Request $request)
    {
        $status = $request->query('status', Apporteur::PENDING);

        $query = Apporteur::with(['user', 'processor'])
            ->withCount('referrals')
            ->withSum(['commissions as earned' => fn ($q) => $q->where('status', 'verse')], 'amount');
        if (in_array($status, self::STATUSES, true)) {
            $query->where('status', $status);
        }
        $list = $query->orderBy($status === Apporteur::PENDING ? 'created_at' : 'id', $status === Apporteur::PENDING ? 'asc' : 'desc')
            ->paginate(20)->withQueryString();

        // Classements : semaine en cours (prime hebdomadaire) et mois en cours (challenge).
        $week = $this->apporteurs->leaderboard(now()->startOfWeek(), now()->endOfWeek());
        $month = $this->apporteurs->leaderboard(now()->startOfMonth(), now()->endOfMonth());

        return view('admin.apporteurs.index', [
            'apporteurs' => $list,
            'status' => $status,
            'week' => $week,
            'month' => $month,
            'pendingCount' => Apporteur::where('status', Apporteur::PENDING)->count(),
        ]);
    }

    public function approve(Request $request, Apporteur $apporteur)
    {
        if (! $this->apporteurs->approve($apporteur, $request->user())) {
            return back()->with('error', 'Cette candidature a déjà été traitée.');
        }

        return back()->with('success', "{$apporteur->user->name} est apporteur. Code : {$apporteur->code}");
    }

    public function reject(Request $request, Apporteur $apporteur)
    {
        $note = $request->validate(['note' => 'nullable|string|max:191'])['note'] ?? null;

        if (! $this->apporteurs->reject($apporteur, $request->user(), $note)) {
            return back()->with('error', 'Cette candidature a déjà été traitée.');
        }

        return back()->with('success', 'Candidature refusée.');
    }

    public function suspend(Request $request, Apporteur $apporteur)
    {
        $note = $request->validate(['note' => 'nullable|string|max:191'])['note'] ?? null;

        return $this->apporteurs->setSuspended($apporteur, true, $request->user(), $note)
            ? back()->with('success', 'Apporteur suspendu : son code n\'est plus accepté et ses gains ne sont plus versés.')
            : back()->with('error', 'Seul un apporteur actif peut être suspendu.');
    }

    public function reactivate(Request $request, Apporteur $apporteur)
    {
        return $this->apporteurs->setSuspended($apporteur, false, $request->user())
            ? back()->with('success', 'Apporteur réactivé.')
            : back()->with('error', 'Cet apporteur n\'est pas suspendu.');
    }
}

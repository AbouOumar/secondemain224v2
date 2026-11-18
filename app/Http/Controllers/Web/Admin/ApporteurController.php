<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Apporteur;
use App\Services\Apporteur\ApporteurService;
use Illuminate\Http\Request;

/**
 * Suivi des apporteurs d'affaires : tout utilisateur a un code de parrainage sans validation ;
 * l'administration surveille, classe et suspend en cas d'abus.
 */
class ApporteurController extends Controller
{
    public function __construct(private ApporteurService $apporteurs) {}

    public function index(Request $request)
    {
        $status = $request->query('status', Apporteur::ACTIVE);

        $query = Apporteur::with(['user', 'processor'])
            ->withCount('referrals')
            ->withSum(['commissions as earned' => fn ($q) => $q->where('status', 'verse')], 'amount');
        if (in_array($status, [Apporteur::ACTIVE, Apporteur::SUSPENDED], true)) {
            $query->where('status', $status);
        }
        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn ($q) => $q->where('code', strtoupper($search))
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")));
        }
        // Les plus actifs d'abord : ceux qui ont parrainé le plus de monde.
        $list = $query->orderByDesc('referrals_count')->orderByDesc('id')->paginate(20)->withQueryString();

        // Classements : semaine en cours (prime hebdomadaire) et mois en cours (challenge).
        return view('admin.apporteurs.index', [
            'apporteurs' => $list,
            'status' => $status,
            'week' => $this->apporteurs->leaderboard(now()->startOfWeek(), now()->endOfWeek()),
            'month' => $this->apporteurs->leaderboard(now()->startOfMonth(), now()->endOfMonth()),
        ]);
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

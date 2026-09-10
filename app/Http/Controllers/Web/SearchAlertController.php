<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SearchAlert;
use App\Services\Search\SearchAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SearchAlertController extends Controller
{
    public function __construct(private SearchAlertService $alerts) {}

    public function index()
    {
        $alerts = Auth::user()->searchAlerts()->with('category')->latest()->get();

        return view('profile.alerts', compact('alerts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'search' => 'nullable|string|max:191',
            'category_id' => 'nullable|exists:categories,id',
            'min_price' => 'nullable|integer|min:0',
            'max_price' => 'nullable|integer|min:0',
            'etat' => 'nullable|string|in:neuf,tres_bon,bon,moyen',
            'localisation' => 'nullable|string|max:191',
        ]);

        if (empty(array_filter($data))) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Ajoutez au moins un critère pour créer une alerte.'], 422);
            }
            return back()->with('error', 'Ajoutez au moins un critère pour créer une alerte.');
        }

        $alert = Auth::user()->searchAlerts()->create($data);
        $this->alerts->initializeCursor($alert);

        if ($request->wantsJson()) {
            return response()->json(['data' => $alert], 201);
        }

        return back()->with('success', 'Alerte créée. Vous serez notifié des nouvelles annonces correspondantes.');
    }

    public function toggle(SearchAlert $alert)
    {
        if ($alert->user_id !== Auth::id()) {
            abort(403);
        }

        $alert->update(['is_active' => ! $alert->is_active]);

        return back()->with('success', $alert->is_active ? 'Alerte activée.' : 'Alerte désactivée.');
    }

    public function destroy(SearchAlert $alert)
    {
        if ($alert->user_id !== Auth::id()) {
            abort(403);
        }

        $alert->delete();

        return back()->with('success', 'Alerte supprimée.');
    }
}

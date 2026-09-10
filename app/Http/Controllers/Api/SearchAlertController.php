<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SearchAlert;
use App\Services\Search\SearchAlertService;
use Illuminate\Http\Request;

class SearchAlertController extends Controller
{
    public function __construct(private SearchAlertService $alerts) {}

    public function index(Request $request) {
        $alerts = $request->user()->searchAlerts()->with('category')->latest()->get();

        return response()->json(['data' => $alerts]);
    }

    public function store(Request $request) {
        $data = $request->validate([
            'search' => 'nullable|string|max:191',
            'category_id' => 'nullable|exists:categories,id',
            'min_price' => 'nullable|integer|min:0',
            'max_price' => 'nullable|integer|min:0',
            'etat' => 'nullable|string|in:neuf,tres_bon,bon,moyen',
            'localisation' => 'nullable|string|max:191',
        ]);

        if (empty(array_filter($data))) {
            return response()->json(['message' => 'Ajoutez au moins un critère pour créer une alerte.'], 422);
        }

        $alert = $request->user()->searchAlerts()->create($data);
        $this->alerts->initializeCursor($alert);

        return response()->json(['data' => $alert], 201);
    }

    public function toggle(Request $request, SearchAlert $alert) {
        if ($alert->user_id !== $request->user()->id) {
            abort(403);
        }

        $alert->update(['is_active' => ! $alert->is_active]);

        return response()->json(['data' => $alert]);
    }

    public function destroy(Request $request, SearchAlert $alert) {
        if ($alert->user_id !== $request->user()->id) {
            abort(403);
        }

        $alert->delete();

        return response()->json(['message' => 'Alerte supprimée.']);
    }
}

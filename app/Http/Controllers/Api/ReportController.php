<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private const REPORTABLE_TYPES = [
        'article' => Article::class,
        'user' => User::class,
    ];

    public function store(Request $request)
    {
        $data = $request->validate([
            'reportable_type' => 'required|string|in:article,user',
            'reportable_id' => 'required|integer',
            'reason' => 'required|string|in:contenu_frauduleux,arnaque,contrefacon,article_interdit,contenu_choquant,harcelement_spam,usurpation_identite,autre',
            'description' => 'nullable|string|max:1000',
        ]);

        $modelClass = self::REPORTABLE_TYPES[$data['reportable_type']];
        $reportable = $modelClass::find($data['reportable_id']);

        if (! $reportable) {
            return response()->json(['message' => 'Élément introuvable.'], 404);
        }

        $ownerId = $reportable instanceof Article ? $reportable->user_id : $reportable->id;
        if ($ownerId === $request->user()->id) {
            return response()->json(['message' => 'Vous ne pouvez pas signaler votre propre contenu.'], 422);
        }

        $alreadyReported = Report::where('reporter_id', $request->user()->id)
            ->where('reportable_type', $modelClass)
            ->where('reportable_id', $reportable->id)
            ->whereIn('status', ['pending', 'reviewing'])
            ->exists();

        if ($alreadyReported) {
            return response()->json(['message' => 'Vous avez déjà signalé cet élément.'], 409);
        }

        $report = Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $modelClass,
            'reportable_id' => $reportable->id,
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Signalement envoyé.', 'id' => $report->id], 201);
    }
}

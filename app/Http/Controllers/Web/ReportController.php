<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * Types signalables, exposés au client (jamais une classe arbitraire
     * fournie par la requête).
     */
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
            return back()->with('error', 'Élément introuvable.');
        }

        $ownerId = $reportable instanceof Article ? $reportable->user_id : $reportable->id;
        if ($ownerId === Auth::id()) {
            return back()->with('error', 'Vous ne pouvez pas signaler votre propre contenu.');
        }

        $alreadyReported = Report::where('reporter_id', Auth::id())
            ->where('reportable_type', $modelClass)
            ->where('reportable_id', $reportable->id)
            ->whereIn('status', ['pending', 'reviewing'])
            ->exists();

        if ($alreadyReported) {
            return back()->with('info', 'Vous avez déjà signalé cet élément, notre équipe l\'examine.');
        }

        Report::create([
            'reporter_id' => Auth::id(),
            'reportable_type' => $modelClass,
            'reportable_id' => $reportable->id,
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Signalement envoyé. Merci, notre équipe va l\'examiner.');
    }
}

<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Report;
use App\Models\User;
use App\Services\Escrow\EscrowService;
use App\Services\Notification\FirebaseNotificationService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private EscrowService $escrow) {}

    public function index(Request $request)
    {
        $query = Report::with(['reporter', 'reportable']);

        $status = $request->get('status', 'pending');
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->filled('type')) {
            $query->where('reportable_type', $request->type === 'article' ? Article::class : User::class);
        }

        $reports = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('admin.reports.index', compact('reports', 'status'));
    }

    public function show(Report $report)
    {
        $report->load(['reporter', 'reportable', 'resolver']);

        return view('admin.reports.show', compact('report'));
    }

    public function resolve(Request $request, Report $report)
    {
        $data = $request->validate([
            'action' => 'required|string|in:dismiss,warn,remove_content,suspend_user',
            'note' => 'nullable|string|max:1000',
        ]);

        $reportable = $report->reportable;

        switch ($data['action']) {
            case 'remove_content':
                if ($reportable instanceof Article) {
                    $reportable->update([
                        'is_published' => false,
                        'rejection_raison' => 'Retirée suite à un signalement : ' . ($data['note'] ?? ''),
                    ]);
                }
                break;

            case 'suspend_user':
                $user = $reportable instanceof User ? $reportable : $reportable?->user;
                $user?->update(['status' => 'suspendu']);

                if ($reportable instanceof Article) {
                    $order = $reportable->orders()->whereHas('escrow', fn ($q) => $q->where('status', 'retenu'))->latest()->first();
                    if ($order?->escrow) {
                        $this->escrow->markDisputed($order->escrow);
                    }
                }
                break;

            case 'warn':
            case 'dismiss':
            default:
                break;
        }

        $notif = app(FirebaseNotificationService::class);
        $owner = $reportable instanceof User ? $reportable : $reportable?->user;
        $note = ($data['note'] ?? null) ? " Motif : {$data['note']}" : '';
        match ($data['action']) {
            'remove_content' => $notif->sendQuietly($owner, 'Annonce retirée', 'Votre annonce a été retirée suite à un signalement.'.$note, 'annonce.retiree', $reportable instanceof Article ? ['article_id' => $reportable->id] : []),
            'suspend_user' => $notif->sendQuietly($owner, 'Compte suspendu', 'Votre compte a été suspendu suite à un signalement.'.$note, 'compte.suspendu'),
            'warn' => $notif->sendQuietly($owner, 'Avertissement', 'Un signalement a été retenu contre votre contenu.'.$note, 'compte.avertissement'),
            default => null,
        };
        $notif->sendQuietly($report->reporter, 'Signalement traité', 'Merci : votre signalement a été examiné par notre équipe.', 'signalement.traite');

        $report->update([
            'status' => $data['action'] === 'dismiss' ? 'dismissed' : 'resolved',
            'resolution_note' => $data['note'] ?? null,
            'resolved_by' => auth()->id(),
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Signalement traité.');
    }
}

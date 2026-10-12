<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Services\Notification\FirebaseNotificationService;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Traitement manuel des retraits du portefeuille : l'équipe verse l'argent par Mobile Money
 * (aucun virement sortant n'est automatisé), puis marque la demande payée ou la refuse.
 */
class WithdrawalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', WithdrawalRequest::PENDING);

        $query = WithdrawalRequest::with(['user', 'processor']);
        if (in_array($status, [WithdrawalRequest::PENDING, WithdrawalRequest::PAID, WithdrawalRequest::REJECTED], true)) {
            $query->where('status', $status);
        }

        $withdrawals = $query->orderByRaw("status = 'en_attente' desc")->orderBy('created_at')->paginate(20)->withQueryString();
        $pendingTotal = WithdrawalRequest::where('status', WithdrawalRequest::PENDING)->sum('montant');

        return view('admin.withdrawals.index', compact('withdrawals', 'status', 'pendingTotal'));
    }

    public function pay(Request $request, WithdrawalRequest $withdrawal, FirebaseNotificationService $notifications)
    {
        $note = $request->validate(['note' => 'nullable|string|max:191'])['note'] ?? null;

        if (! $this->finish($withdrawal, WithdrawalRequest::PAID, $note, $request->user()->id)) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $notifications->send(
            $withdrawal->user,
            'Retrait envoyé',
            number_format($withdrawal->montant, 0, ',', ' ') . " GNF ont été envoyés au {$withdrawal->phone}.",
            'retrait.traite',
            ['withdrawal_id' => $withdrawal->id],
        );

        return back()->with('success', "Retrait {$withdrawal->reference} marqué comme payé.");
    }

    public function reject(Request $request, WithdrawalRequest $withdrawal, WalletService $wallet, FirebaseNotificationService $notifications)
    {
        $note = $request->validate(['note' => 'nullable|string|max:191'])['note'] ?? null;

        $rejected = DB::transaction(function () use ($withdrawal, $note, $request, $wallet) {
            if (! $this->finish($withdrawal, WithdrawalRequest::REJECTED, $note, $request->user()->id)) {
                return false;
            }
            // Le montant avait été débité à la demande : il retourne dans le portefeuille.
            $wallet->credit($withdrawal->user_id, $withdrawal->montant, 'remboursement', $withdrawal->id, "Retrait refusé {$withdrawal->reference}");

            return true;
        });

        if (! $rejected) {
            return back()->with('error', 'Cette demande a déjà été traitée.');
        }

        $notifications->send(
            $withdrawal->user,
            'Retrait refusé',
            'Votre retrait a été refusé' . ($note ? " : {$note}." : '.') . ' Le montant est revenu dans votre portefeuille.',
            'retrait.traite',
            ['withdrawal_id' => $withdrawal->id],
        );

        return back()->with('success', "Retrait {$withdrawal->reference} refusé et remboursé.");
    }

    /** Fait passer une demande en attente à son état final ; faux si quelqu'un l'a déjà traitée. */
    private function finish(WithdrawalRequest $withdrawal, string $status, ?string $note, int $adminId): bool
    {
        $changed = WithdrawalRequest::whereKey($withdrawal->id)
            ->where('status', WithdrawalRequest::PENDING)
            ->update(['status' => $status, 'note' => $note, 'processed_by' => $adminId, 'processed_at' => now()]);

        return $changed === 1;
    }
}

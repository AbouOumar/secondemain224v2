<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Djomy\DjomyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private DjomyService $djomy,
    ) {}

    public function show(Order $order)
    {
        if ($order->buyer_id !== Auth::id()) {
            abort(403);
        }

        if ($order->status->value !== 'en_attente_paiement') {
            return redirect()->route('articles.show', $order->article->slug)
                ->with('info', 'Cette commande a déjà été traitée.');
        }

        $existingPayment = $order->payments()->where('methode', 'djomy')->latest()->first();

        return view('payment.show', compact('order', 'existingPayment'));
    }

    public function processDjomy(Request $request, Order $order)
    {
        if ($order->buyer_id !== Auth::id()) {
            abort(403);
        }

        if ($order->status->value !== 'en_attente_paiement') {
            return redirect()->route('articles.show', $order->article->slug)
                ->with('info', 'Cette commande a déjà été traitée.');
        }

        $data = $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        $reference = 'PAY-' . strtoupper(Str::random(10));

        $result = $this->djomy->initiateGatewayPayment([
            'amount' => (int) $order->total,
            'payer_phone' => $data['phone'],
            'description' => "Paiement commande {$order->reference}",
            'reference' => $reference,
            'return_url' => route('payment.callback', ['order' => $order, 'status' => 'success']),
            'cancel_url' => route('payment.callback', ['order' => $order, 'status' => 'cancel']),
            'metadata' => [
                'order_id' => (string) $order->id,
                'buyer_id' => (string) Auth::id(),
            ],
        ]);

        if (!$result['success']) {
            Log::error('Djomy payment initiation failed', [
                'order_id' => $order->id,
                'result' => $result,
            ]);
            $msg = $result['error']['message'] ?? 'Échec de l\'initiation du paiement.';
            return redirect()->route('payment.show', $order)->with('error', $msg);
        }

        Payment::create([
            'reference' => $reference,
            'order_id' => $order->id,
            'user_id' => Auth::id(),
            'montant' => $order->total,
            'currency' => 'GNF',
            'methode' => 'djomy',
            'status' => 'en_attente',
            'external_ref' => $result['data']['transactionId'] ?? null,
            'external_data' => $result,
        ]);

        $redirectUrl = $result['data']['redirectUrl'] ?? null;

        if ($redirectUrl) {
            return redirect()->away($redirectUrl);
        }

        return redirect()->route('payment.show', $order)
            ->with('info', 'Paiement initié. Veuillez vérifier votre téléphone pour confirmer.');
    }

    public function callback(Request $request, Order $order)
    {
        if ($order->buyer_id !== Auth::id()) {
            abort(403);
        }

        $status = $request->status;

        if ($status === 'success') {
            return redirect()->route('articles.show', $order->article->slug)
                ->with('success', 'Paiement réussi ! Votre commande est en cours de traitement.');
        }

        return redirect()->route('payment.show', $order)
            ->with('error', 'Paiement annulé ou échoué. Veuillez réessayer.');
    }
}

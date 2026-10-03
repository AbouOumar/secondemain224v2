<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessPaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Http\Resources\PaymentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\Wallet\WalletService;
use App\Services\Payment\OrangeMoneyService;
use App\Services\Payment\MtnMomoService;
use App\Services\Payment\CardPaymentService;
use App\Services\Djomy\DjomyService;
use App\Events\PaymentReceived;

class PaymentController extends Controller
{
    public function __construct(
        private OrangeMoneyService $orangeMoney,
        private MtnMomoService $mtnMomo,
        private CardPaymentService $cardPayment,
        private DjomyService $djomy,
        private WalletService $wallet,
    ) {}

    private function mapDjomyPaymentMethod(string $methode): string
    {
        return match ($methode) {
            'orange_money' => 'OM',
            'mtn_momo' => 'MOMO',
            'carte_bancaire' => 'CARD',
            default => 'OM',
        };
    }

    public function initiate(ProcessPaymentRequest $request) {
        $order = Order::with('article')->findOrFail($request->order_id);
        $this->authorize('pay', $order);
        $reference = 'PAY-' . strtoupper(Str::random(10));

        if ($request->methode === 'djomy') {
            $result = $this->djomy->initiateGatewayPayment([
                'amount' => (int) $order->total,
                'country_code' => config('djomy.country_code'),
                'payer_phone' => $request->payer_phone ?? $request->user()->phone,
                'description' => "Paiement commande {$order->reference}",
                'reference' => $reference,
                'return_url' => $request->return_url,
                'cancel_url' => $request->cancel_url,
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'buyer_id' => (string) $request->user()->id,
                ],
            ]);

            $payment = Payment::create([
                'reference' => $reference,
                'order_id' => $order->id,
                'user_id' => $request->user()->id,
                'montant' => $order->total,
                'currency' => $order->article->currency ?? 'GNF',
                'methode' => 'djomy',
                'status' => 'en_attente',
                'external_ref' => $result['data']['transactionId'] ?? null,
                'external_data' => $result,
            ]);

            if (!$result['success']) {
                $payment->update(['status' => 'echoue', 'external_data' => $result]);
                return response()->json([
                    'message' => 'Échec de l\'initiation du paiement',
                    'error' => $result['error'] ?? null,
                ], 422);
            }

            return response()->json([
                'payment' => new PaymentResource($payment),
                'redirect_url' => $result['data']['redirectUrl'] ?? null,
                'transaction_id' => $result['data']['transactionId'] ?? null,
            ], 201);
        }

        $gateway = match ($request->methode) {
            'orange_money' => $this->orangeMoney,
            'mtn_momo' => $this->mtnMomo,
            'carte_bancaire' => $this->cardPayment,
            default => throw new \InvalidArgumentException("Méthode de paiement invalide"),
        };

        $result = $gateway->initiatePayment(
            $order->total,
            $order->article->currency ?? 'GNF',
            $reference,
            ['order_id' => $order->id, 'buyer_id' => $request->user()->id]
        );

        $payment = Payment::create([
            'reference' => $reference,
            'order_id' => $order->id,
            'user_id' => $request->user()->id,
            'montant' => $order->total,
            'currency' => $order->article->currency ?? 'GNF',
            'methode' => $request->methode,
            'status' => 'en_attente',
            'external_ref' => $result['external_ref'] ?? null,
            'external_data' => $result,
        ]);

        return response()->json([
            'payment' => new PaymentResource($payment),
            'redirect_url' => $result['redirect_url'] ?? null,
            'payment_token' => $result['payment_token'] ?? null,
        ], 201);
    }

    public function djomyWebhook(Request $request) {
        $signature = (string) $request->header('X-Webhook-Signature', '');
        $payload = $request->getContent();

        if (!$this->djomy->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Djomy webhook signature mismatch');
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $data = $request->input('data');
        $eventType = $request->input('eventType');

        if (!$data || !$eventType) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $transactionId = $data['transactionId'] ?? null;
        $merchantRef = $data['merchantPaymentReference'] ?? null;

        // Sans aucune référence, `where(..., null)` retrouverait n'importe quel paiement sans référence externe.
        if (! $merchantRef && ! $transactionId) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $payment = $merchantRef
            ? Payment::where('reference', $merchantRef)->first()
            : Payment::where('external_ref', $transactionId)->first();

        if (!$payment) {
            Log::warning('Djomy webhook: payment not found', [
                'transactionId' => $transactionId,
                'merchantRef' => $merchantRef,
            ]);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // Sous verrou : deux notifications simultanées pour le même paiement ne l'encaissent qu'une fois.
        $confirmed = DB::transaction(function () use ($payment, $data, $transactionId, $request) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->first();

            // Un paiement déjà encaissé ne revient pas en arrière (notification tardive ou rejouée).
            if ($locked->status->value === 'succes') {
                return null;
            }

            $status = match ($data['status'] ?? '') {
                'SUCCESS', 'CAPTURED' => 'succes',
                'FAILED', 'TIMEOUT' => 'echoue',
                'CANCELLED' => 'annule',
                default => $locked->status->value,
            };

            $locked->update([
                'status' => $status,
                'paid_at' => $status === 'succes' ? now() : $locked->paid_at,
                'external_ref' => $transactionId ?? $locked->external_ref,
                'external_data' => $request->all(),
            ]);

            if ($status !== 'succes') {
                return null;
            }

            // Sans commande : c'est une recharge du portefeuille.
            if ($locked->order_id === null) {
                $this->wallet->credit(
                    $locked->user_id,
                    (int) $locked->montant,
                    'paiement',
                    $locked->id,
                    "Recharge du portefeuille — {$locked->reference}",
                );
                return null;
            }

            return $locked;
        });

        if ($confirmed) {
            event(new PaymentReceived($confirmed));
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * Les passerelles Orange Money et MTN MoMo directes ne sont pas intégrées (Djomy les regroupe).
     * Ces adresses ne vérifiaient aucune signature et marquaient « payé » le premier paiement sans
     * référence externe : elles sont fermées, sans aucun effet.
     */
    public function omWebhook() {
        return response()->json(['message' => 'Gone'], 410);
    }

    public function momoWebhook() {
        return response()->json(['message' => 'Gone'], 410);
    }

    public function show($reference) {
        $payment = Payment::where('reference', $reference)->with('order')->firstOrFail();
        if ($payment->order) {
            $this->authorize('pay', $payment->order);
        } else {
            // Recharge de portefeuille : seul son auteur la suit.
            abort_unless($payment->user_id === request()->user()->id, 403);
        }
        return new PaymentResource($payment);
    }
}

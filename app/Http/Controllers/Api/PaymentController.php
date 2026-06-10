<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessPaymentRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Http\Resources\PaymentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
        $signature = $request->header('X-Webhook-Signature');
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

        $status = match ($data['status'] ?? '') {
            'SUCCESS', 'CAPTURED' => 'succes',
            'FAILED', 'TIMEOUT' => 'echoue',
            'CANCELLED' => 'annule',
            default => $payment->status->value,
        };

        $payment->update([
            'status' => $status,
            'paid_at' => in_array($status, ['succes']) ? now() : $payment->paid_at,
            'external_ref' => $transactionId ?? $payment->external_ref,
            'external_data' => $request->all(),
        ]);

        if ($status === 'succes') {
            event(new PaymentReceived($payment));
        }

        return response()->json(['message' => 'OK']);
    }

    public function omWebhook(Request $request) {
        $result = $this->orangeMoney->processWebhook($request->all());
        $payment = Payment::where('external_ref', $result['external_ref'])->first();
        if ($payment) {
            $payment->update([
                'status' => $result['status'] === 'success' ? 'complete' : 'echoue',
                'paid_at' => $result['status'] === 'success' ? now() : null,
                'external_data' => $request->all(),
            ]);
            if ($result['status'] === 'success') {
                event(new PaymentReceived($payment));
            }
        }
        return response()->json(['message' => 'OK']);
    }

    public function momoWebhook(Request $request) {
        $result = $this->mtnMomo->processWebhook($request->all());
        $payment = Payment::where('external_ref', $result['external_ref'])->first();
        if ($payment) {
            $payment->update([
                'status' => $result['status'] === 'success' ? 'complete' : 'echoue',
                'paid_at' => $result['status'] === 'success' ? now() : null,
                'external_data' => $request->all(),
            ]);
            if ($result['status'] === 'success') {
                event(new PaymentReceived($payment));
            }
        }
        return response()->json(['message' => 'OK']);
    }

    public function show($reference) {
        $payment = Payment::where('reference', $reference)->with('order')->firstOrFail();
        abort_unless($payment->order, 404);
        $this->authorize('pay', $payment->order);
        return new PaymentResource($payment);
    }
}

<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\WithdrawalResource;
use App\Models\Payment;
use App\Models\WithdrawalRequest;
use App\Services\Djomy\DjomyService;
use App\Services\Wallet\InsufficientFundsException;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    public const MIN_TOPUP = 1000;
    public const MAX_TOPUP = 5000000;

    public function show(Request $request) {
        $wallet = $request->user()->wallet()->firstOrCreate(
            ['user_id' => $request->user()->id],
            ['balance' => 0, 'currency' => 'GNF']
        );
        return response()->json([
            'balance' => (float) $wallet->balance,
            'currency' => $wallet->currency,
        ]);
    }

    public function transactions(Request $request) {
        $wallet = $request->user()->wallet;
        if (!$wallet) {
            return response()->json(['data' => []]);
        }
        $transactions = $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return TransactionResource::collection($transactions);
    }

    public function withdrawals(Request $request) {
        $items = WithdrawalRequest::where('user_id', $request->user()->id)->latest()->paginate(15);

        return WithdrawalResource::collection($items);
    }

    /**
     * Demande de retrait vers Mobile Money. Le montant est débité tout de suite (il ne peut pas
     * être dépensé entre-temps) ; l'équipe verse l'argent puis marque la demande payée, ou la
     * refuse, auquel cas le montant revient dans le portefeuille.
     */
    public function requestWithdrawal(Request $request, WalletService $wallet) {
        $data = $request->validate([
            'montant' => 'required|integer|min:' . WithdrawalRequest::MIN_AMOUNT,
            'phone' => 'required|string|max:20',
            'operator' => 'required|string|in:orange_money,mtn_momo',
        ]);
        $user = $request->user();

        if (WithdrawalRequest::where('user_id', $user->id)->where('status', WithdrawalRequest::PENDING)->exists()) {
            return response()->json(['message' => 'Un retrait est déjà en cours de traitement. Attendez qu\'il soit terminé.'], 409);
        }

        try {
            $withdrawal = DB::transaction(function () use ($user, $data, $wallet) {
                $withdrawal = WithdrawalRequest::create([
                    'reference' => 'RET-' . strtoupper(Str::random(10)),
                    'user_id' => $user->id,
                    'montant' => $data['montant'],
                    'phone' => $data['phone'],
                    'operator' => $data['operator'],
                    'status' => WithdrawalRequest::PENDING,
                ]);
                $wallet->debit($user->id, (int) $data['montant'], 'retrait', $withdrawal->id, "Retrait {$withdrawal->reference}");

                return $withdrawal;
            });
        } catch (InsufficientFundsException) {
            return response()->json(['message' => 'Solde insuffisant pour ce retrait.'], 422);
        }

        return response()->json(['data' => new WithdrawalResource($withdrawal)], 201);
    }

    /**
     * Recharge par Djomy (Orange Money, MTN MoMo, carte). Le portefeuille n'est crédité
     * qu'à la confirmation du paiement par le webhook signé, jamais ici.
     */
    public function topup(Request $request, DjomyService $djomy) {
        $data = $request->validate([
            'montant' => 'required|integer|min:' . self::MIN_TOPUP . '|max:' . self::MAX_TOPUP,
            'payer_phone' => 'required|string|max:20',
            'return_url' => 'nullable|url',
            'cancel_url' => 'nullable|url',
        ]);

        $user = $request->user();
        $reference = 'PAY-' . strtoupper(Str::random(10));

        $result = $djomy->initiateGatewayPayment([
            'amount' => (int) $data['montant'],
            'country_code' => config('djomy.country_code'),
            'payer_phone' => $data['payer_phone'],
            'description' => 'Recharge du portefeuille',
            'reference' => $reference,
            'return_url' => $data['return_url'] ?? null,
            'cancel_url' => $data['cancel_url'] ?? null,
            'metadata' => ['type' => 'wallet_topup', 'user_id' => (string) $user->id],
        ]);

        // Sans commande liée (order_id nul) : c'est ce qui distingue une recharge d'un paiement d'achat.
        $payment = Payment::create([
            'reference' => $reference,
            'order_id' => null,
            'user_id' => $user->id,
            'montant' => $data['montant'],
            'currency' => 'GNF',
            'methode' => 'djomy',
            'status' => $result['success'] ? 'en_attente' : 'echoue',
            'external_ref' => $result['data']['transactionId'] ?? null,
            'external_data' => $result,
        ]);

        if (! $result['success']) {
            return response()->json([
                'message' => $result['error']['message'] ?? "Impossible de lancer le paiement. Vérifiez votre numéro.",
            ], 422);
        }

        return response()->json([
            'payment' => new PaymentResource($payment),
            'redirect_url' => $result['data']['redirectUrl'] ?? null,
        ], 201);
    }
}

<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use Illuminate\Http\Request;

class WalletController extends Controller
{
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
}

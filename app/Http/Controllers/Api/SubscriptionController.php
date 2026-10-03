<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\SubscriptionException;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\Request;

/**
 * Abonnements des revendeurs pro (routes réservées au rôle revendeur_pro). Le plan payant est
 * débité du portefeuille ; le badge « vérifié » dure autant que l'abonnement.
 */
class SubscriptionController extends Controller
{
    public function __construct(private SubscriptionService $subscriptions) {}

    public function plans()
    {
        $plans = collect($this->subscriptions->plans())->map(fn (array $plan, string $key) => [
            'key' => $key,
            'label' => $plan['label'],
            'tagline' => $plan['tagline'],
            'price' => (int) $plan['price'],
            'badge' => (bool) $plan['badge'],
            'features' => $plan['features'],
        ])->values();

        return response()->json(['data' => $plans, 'duration_days' => (int) config('subscriptions.duration_days')]);
    }

    public function current(Request $request)
    {
        $subscription = Subscription::where('user_id', $request->user()->id)->first();

        return response()->json(['subscription' => $subscription ? $this->format($subscription) : null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['plan_type' => 'required|string|in:basic,pro,enterprise']);

        try {
            $subscription = $this->subscriptions->subscribe($request->user(), $data['plan_type']);
        } catch (SubscriptionException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status);
        }

        return response()->json(['message' => 'Abonnement activé.', 'subscription' => $this->format($subscription)], 201);
    }

    public function cancel(Request $request)
    {
        if (! $this->subscriptions->cancel($request->user())) {
            return response()->json(['message' => "Vous n'avez pas d'abonnement actif."], 404);
        }

        return response()->json(['message' => 'Abonnement annulé.']);
    }

    public function history(Request $request)
    {
        $subscriptions = $request->user()->subscriptions()->orderBy('created_at', 'desc')->get();

        return response()->json(['subscriptions' => $subscriptions->map(fn (Subscription $s) => $this->format($s))]);
    }

    private function format(Subscription $subscription): array
    {
        $plan = config("subscriptions.plans.{$subscription->plan_type}");

        return [
            'id' => $subscription->id,
            'plan_type' => $subscription->plan_type,
            'label' => $plan['label'] ?? $subscription->plan_type,
            'status' => $subscription->status,
            'is_active' => $subscription->isActive(),
            'starts_at' => $subscription->starts_at,
            'ends_at' => $subscription->ends_at,
        ];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    /**
     * Get current user's subscription
     */
    public function current(Request $request)
    {
        $subscription = Auth::user()->subscription;
        
        if (!$subscription) {
            return response()->json([
                'message' => 'No active subscription found',
                'subscription' => null
            ], 404);
        }
        
        return response()->json([
            'subscription' => $subscription
        ]);
    }

    /**
     * Create or update subscription
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plan_type' => 'required|in:basic,pro,enterprise',
            'payment_method' => 'required|string',
            // In a real implementation, you would validate payment details here
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $subscription = $user->subscription()
            ->updateOrCreate(
                [],
                [
                    'plan_type' => $request->plan_type,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => now()->addMonth(), // Monthly subscription
                    'provider' => $request->payment_method,
                ]
            );

        // Update user's verified status for pro plan
        if ($request->plan_type === 'pro') {
            $user->update([
                'is_verified' => true,
                'verified_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Subscription created successfully',
            'subscription' => $subscription
        ], 201);
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request)
    {
        $subscription = Auth::user()->subscription;

        if (!$subscription) {
            return response()->json([
                'message' => 'No active subscription found'
            ], 404);
        }

        $subscription->update([
            'status' => 'cancelled',
            'ends_at' => now(),
        ]);

        // If cancelling pro plan, remove verified status
        if ($subscription->plan_type === 'pro') {
            Auth::user()->update([
                'is_verified' => false,
            ]);
        }

        return response()->json([
            'message' => 'Subscription cancelled successfully'
        ]);
    }

    /**
     * Get subscription history
     */
    public function history(Request $request)
    {
        $subscriptions = Auth::user()->subscriptions()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'subscriptions' => $subscriptions
        ]);
    }
}
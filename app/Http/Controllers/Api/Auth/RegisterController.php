<?php
namespace App\Http\Controllers\Api\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\RoleRequest;
use App\Models\User;
use App\Services\Account\RoleRequestService;
use App\Models\Wallet;
use App\Notifications\Auth\WelcomeNotification;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource;

class RegisterController extends Controller
{
    public function store(RegisterRequest $request) {
        // Livreur et revendeur pro s'obtiennent sur demande, après examen : on crée un compte acheteur
        // et une demande en attente (le candidat donne ses informations ensuite, depuis son compte).
        $requested = $request->role ?? 'acheteur';
        $needsReview = in_array($requested, RoleRequest::REQUESTABLE, true);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $needsReview ? 'acheteur' : $requested,
            'status' => 'actif',
        ]);
        if ($needsReview) {
            app(RoleRequestService::class)->request($user, $requested, null);
        }
        if ($request->boolean('newsletter')) {
            $user->forceFill(['newsletter_subscribed_at' => now()])->save();
        }
        Wallet::create(['user_id' => $user->id, 'balance' => 0, 'currency' => 'GNF']);
        $user->notify(new WelcomeNotification);
        $token = $user->createToken('auth-token')->plainTextToken;
        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }
}

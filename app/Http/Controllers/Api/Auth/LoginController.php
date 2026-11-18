<?php
namespace App\Http\Controllers\Api\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\LoginAttemptGuard;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource;

class LoginController extends Controller
{
    public function store(LoginRequest $request, LoginAttemptGuard $guard) {
        $login = (string) $request->login;
        $ip = (string) $request->ip();
        $user = $guard->findUser($login);

        if ($wait = $guard->lockedFor($login, $ip, $user)) {
            return response()->json(['message' => $guard->lockedMessage($wait)], 429, ['Retry-After' => $wait]);
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            $remaining = $guard->failed($login, $ip, $user);
            if ($remaining === 0) {
                $wait = $guard->lockedFor($login, $ip, $user);
                return response()->json(['message' => $guard->lockedMessage($wait)], 429, ['Retry-After' => $wait]);
            }
            return response()->json(['message' => $guard->failureMessage($remaining), 'remaining_attempts' => $remaining], 401);
        }

        $guard->succeeded($login, $ip, $user);

        if ($user->status?->value === 'suspendu') {
            return response()->json(['message' => 'Ce compte a été suspendu.'], 403);
        }
        $token = $user->createToken('auth-token')->plainTextToken;
        $user->update(['last_online_at' => now()]);
        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }
}

<?php
namespace App\Http\Controllers\Api\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;

class SocialAuthController extends Controller
{
    public function google(Request $request) {
        $request->validate(['token' => 'required|string']);
        return $this->socialLogin('google', $request->token);
    }

    public function facebook(Request $request) {
        $request->validate(['token' => 'required|string']);
        return $this->socialLogin('facebook', $request->token);
    }

    private function socialLogin(string $provider, string $token) {
        $email = $token . '@' . $provider . '.com';
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'User_' . Str::random(6),
                'phone' => '000' . Str::random(8),
                'password' => Hash::make(Str::random(20)),
                'role' => 'acheteur',
                'status' => 'actif',
            ]
        );
        Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0, 'currency' => 'GNF']);
        $token = $user->createToken('auth-token')->plainTextToken;
        return response()->json(['user' => new UserResource($user), 'token' => $token]);
    }
}

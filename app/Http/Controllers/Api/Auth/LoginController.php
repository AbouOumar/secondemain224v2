<?php
namespace App\Http\Controllers\Api\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource;

class LoginController extends Controller
{
    public function store(LoginRequest $request) {
        $user = User::where('email', $request->login)->orWhere('phone', $request->login)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }
        $token = $user->createToken('auth-token')->plainTextToken;
        $user->update(['last_online_at' => now()]);
        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }
}

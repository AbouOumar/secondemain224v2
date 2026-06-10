<?php
namespace App\Http\Controllers\Api\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\UserResource;

class RegisterController extends Controller
{
    public function store(RegisterRequest $request) {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'acheteur',
            'status' => 'actif',
        ]);
        Wallet::create(['user_id' => $user->id, 'balance' => 0, 'currency' => 'GNF']);
        $token = $user->createToken('auth-token')->plainTextToken;
        return response()->json([
            'user' => new UserResource($user),
            'token' => $token,
        ], 201);
    }
}

<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request) {
        $query = User::query();
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $users = $query->orderBy('created_at', 'desc')->paginate(15);
        return UserResource::collection($users);
    }

    public function show(User $user) {
        return new UserResource($user);
    }

    public function suspend(User $user) {
        $user->update(['status' => 'suspendu']);
        return new UserResource($user);
    }

    public function activate(User $user) {
        $user->update(['status' => 'actif']);
        return new UserResource($user);
    }

    public function destroy(User $user) {
        $user->delete();
        return response()->json(null, 204);
    }
}

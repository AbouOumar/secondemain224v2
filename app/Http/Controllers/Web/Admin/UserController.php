<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load(['articles' => fn ($q) => $q->latest()->limit(10), 'ordersAsBuyer', 'ordersAsSeller', 'partner']);

        return view('admin.users.show', compact('user'));
    }

    public function suspend(User $user)
    {
        $user->update(['status' => 'suspendu']);

        return back()->with('success', "Le compte de {$user->name} a été suspendu.");
    }

    public function activate(User $user)
    {
        $user->update(['status' => 'actif']);

        return back()->with('success', "Le compte de {$user->name} a été réactivé.");
    }

    public function destroy(User $user)
    {
        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', "Le compte de {$name} a été supprimé.");
    }
}

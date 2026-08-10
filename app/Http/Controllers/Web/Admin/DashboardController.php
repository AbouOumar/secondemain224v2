<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'total_articles' => Article::count(),
            'total_orders' => Order::count(),
            'total_revenue' => Payment::where('status', 'succes')->sum('montant'),
            'pending_moderation' => Article::where('is_verified', false)->where('is_published', true)->count(),
            'pending_partners' => \App\Models\Partner::where('is_verified', false)->count(),
        ];

        $usersByRole = User::selectRaw('role, count(*) as count')->groupBy('role')->pluck('count', 'role');

        $articlesByCategory = Article::selectRaw('category_id, count(*) as count')
            ->groupBy('category_id')
            ->with('category:id,libelle')
            ->get();

        $recentOrders = Order::with(['article', 'buyer', 'seller'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        $recentUsers = User::orderBy('created_at', 'desc')->limit(8)->get();

        return view('admin.dashboard', compact('stats', 'usersByRole', 'articlesByCategory', 'recentOrders', 'recentUsers'));
    }
}

<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Article;
use App\Models\Order;
use App\Models\Payment;

class DashboardController extends Controller
{
    public function index() {
        return response()->json([
            'total_users' => User::count(),
            'total_articles' => Article::count(),
            'total_orders' => Order::count(),
            'total_revenue' => Payment::where('status', 'complete')->sum('montant'),
            'users_by_role' => User::selectRaw('role, count(*) as count')->groupBy('role')->get(),
            'articles_by_category' => Article::selectRaw('category_id, count(*) as count')
                ->groupBy('category_id')->with('category:id,libelle')->get(),
        ]);
    }
}

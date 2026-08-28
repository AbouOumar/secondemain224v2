<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\User;
use App\Services\Seller\SellerStatsService;

class SellerProfileController extends Controller
{
    public function show(User $user, SellerStatsService $sellerStats)
    {
        $stats = $sellerStats->getStats($user);

        $articles = Article::where('user_id', $user->id)
            ->where('is_published', true)
            ->where(fn ($q) => $q->where('statut', '!=', 'vendu')->orWhereNull('statut'))
            ->with(['images', 'category'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('seller.public-profile', compact('user', 'stats', 'articles'));
    }
}

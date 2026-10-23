<?php

namespace App\Services\Seller;

use App\Http\Resources\OrderResource;
use App\Models\Article;
use App\Models\Order;
use App\Models\User;

/**
 * Chiffres du tableau de bord d'un vendeur (particulier ou boutique) : annonces,
 * ventes, gains par période et dernières commandes. Partagé par les routes
 * `/seller/dashboard` et `/shop/dashboard` pour qu'elles donnent les mêmes valeurs.
 */
class SellerDashboard
{
    public function for(User $seller): array
    {
        $now = now();

        $total = Article::where('user_id', $seller->id)->count();
        $enVente = Article::where('user_id', $seller->id)->where('is_published', true)
            ->where(fn ($q) => $q->where('statut', '!=', 'vendu')->orWhereNull('statut'))->count();
        $vendus = Order::where('seller_id', $seller->id)->where('status', 'livre')->distinct('article_id')->count('article_id');

        $paid = Order::where('seller_id', $seller->id)->whereIn('status', ['paye', 'livre']);
        $earnings = [
            'aujourd_hui' => (int) (clone $paid)->whereDate('created_at', $now->toDateString())->sum('total'),
            'cette_semaine' => (int) (clone $paid)->whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])->sum('total'),
            'ce_mois' => (int) (clone $paid)->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)->sum('total'),
            'cette_annee' => (int) (clone $paid)->whereYear('created_at', $now->year)->sum('total'),
        ];

        $recent = Order::where('seller_id', $seller->id)->with(['article', 'buyer'])->latest()->take(5)->get();

        return [
            'total' => $total,
            'en_vente' => $enVente,
            'vendus' => $vendus,
            'taux' => $total ? (int) round(($vendus / $total) * 100) : 0,
            'earnings' => $earnings,
            'recent_orders' => OrderResource::collection($recent),
        ];
    }
}

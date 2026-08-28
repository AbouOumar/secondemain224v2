<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Seller\SellerStatsService;

class SellerController extends Controller
{
    public function stats(User $user, SellerStatsService $sellerStats)
    {
        return response()->json(['data' => $sellerStats->getStats($user)]);
    }
}

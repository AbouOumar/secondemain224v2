<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Seller\SellerDashboard;
use Illuminate\Http\Request;

class SellerDashboardController extends Controller
{
    public function show(Request $request, SellerDashboard $dashboard)
    {
        return response()->json(['data' => $dashboard->for($request->user())]);
    }
}

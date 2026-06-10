<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRiderAvailability
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->rider_status === 'occupe') {
            return response()->json(['message' => 'Vous êtes déjà en livraison.'], 403);
        }
        return $next($request);
    }
}

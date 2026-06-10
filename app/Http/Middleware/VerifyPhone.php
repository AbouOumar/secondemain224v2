<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPhone
{
    public function handle(Request $request, Closure $next): Response
    {
        if (is_null($request->user()?->phone_verified_at)) {
            return response()->json(['message' => 'Numéro de téléphone non vérifié.'], 403);
        }
        return $next($request);
    }
}

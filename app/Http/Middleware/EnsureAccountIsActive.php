<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un compte suspendu (par l'administration, ou supprimé) perd l'accès à l'API,
 * même avec un jeton encore valide.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->status?->value === 'suspendu') {
            return response()->json(['message' => 'Ce compte a été suspendu.'], 403);
        }

        return $next($request);
    }
}

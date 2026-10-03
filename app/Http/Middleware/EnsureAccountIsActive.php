<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un compte suspendu (par l'administration, ou supprimé) perd l'accès à l'API,
 * même avec un jeton encore valide. Un jeton à portée limitée n'ouvre que sa route.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->status?->value === 'suspendu') {
            return response()->json(['message' => 'Ce compte a été suspendu.'], 403);
        }

        // Jeton à portée limitée (position du livreur, conservé en clair par le service Android) :
        // il ne sert qu'à envoyer la position, jamais à agir sur le compte.
        $token = $request->user()?->currentAccessToken();
        $positionOnly = $token && $token->can('position') && ! $token->can('*');
        if ($positionOnly && ! $request->routeIs('api.deliveries.position')) {
            return response()->json(['message' => 'Ce jeton ne permet que d\'envoyer la position.'], 403);
        }

        return $next($request);
    }
}

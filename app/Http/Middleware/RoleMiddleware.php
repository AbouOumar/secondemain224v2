<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->user()?->role;
        $roleValue = $role instanceof \BackedEnum ? $role->value : $role;

        if (!in_array($roleValue, $roles, true)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Accès non autorisé.'], 403);
            }
            abort(403, 'Accès non autorisé.');
        }
        return $next($request);
    }
}

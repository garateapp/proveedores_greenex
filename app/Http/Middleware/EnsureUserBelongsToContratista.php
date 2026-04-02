<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserBelongsToContratista
{
    /**
     * Handle an incoming request.
     *
     * This middleware ensures that non-admin users can only access
     * resources that belong to their contratista.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Admins can access everything
        if ($user && $user->isAdmin()) {
            return $next($request);
        }

        // Non-admin users must belong to a contratista
        if ($user && ! $user->contratista_id) {
            abort(403, 'Usuario no asociado a ningún contratista.');
        }

        // Share contratista_id with all views and requests
        if ($user && $user->contratista_id) {
            $request->merge(['contratista_id' => $user->contratista_id]);
            view()->share('currentContratistaId', $user->contratista_id);
        }

        return $next($request);
    }
}

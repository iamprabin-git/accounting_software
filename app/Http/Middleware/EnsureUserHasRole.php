<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        $allowed = [];
        foreach ($roles as $chunk) {
            foreach (array_map('trim', explode(',', $chunk)) as $role) {
                if ($role !== '') {
                    $allowed[] = $role;
                }
            }
        }

        if (! $user || ! in_array($user->role, $allowed, true)) {
            abort(403);
        }

        return $next($request);
    }
}

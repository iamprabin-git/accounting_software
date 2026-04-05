<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->canAccessCustomerApp()) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login', absolute: false)
                ->with('status', __('Your account is inactive or your subscription has ended. Please contact support.'));
        }

        return $next($request);
    }
}

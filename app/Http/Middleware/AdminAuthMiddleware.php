<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()
                ->route('admin.login')
                ->withErrors([
                    'login' => 'Your session has expired. Please login again.',
                ]);
        }

        if (auth()->user()->role !== 'admin') {
            auth()->logout();

            return redirect()
                ->route('admin.login')
                ->withErrors([
                    'login' => 'Admin access required.',
                ]);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EnsureIsStudent
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::check() === false || Auth::guard('instructor')->check()) {
            return new JsonResponse([
                'message' => 'Unauthorized'
            ], 401);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Model\Instructor;

class EnsureIsManager
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
        $instructor = Instructor::find(Auth::guard('instructor')->id());
        if ($instructor->type !== Instructor::TYPE_MANAGER) {
            return new JsonResponse([
                'message' => 'Forbidden, not allowed to use manager api.'
            ], 403);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Enums\Instructor\TypeEnum as InstructorTypeEnum;
use App\Model\Instructor;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureIsManager
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $instructor = Instructor::find(Auth::guard('instructor')->id());
        if ($instructor->type !== InstructorTypeEnum::MANAGER) {
            return new JsonResponse([
                'message' => 'Forbidden, not allowed to use manager api.',
            ], 403);
        }

        return $next($request);
    }
}

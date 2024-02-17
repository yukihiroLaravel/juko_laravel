<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class InstructorLoginController extends Controller
{
    /**
     * @param LoginRequest $request
     * @return JsonResponse
     * @throws AuthenticationException
     */
    public function __invoke(LoginRequest $request)
    {
        if (Auth::guard('instructor')->attempt($request->only(['email', 'password']))) {
            $request->session()->regenerate();
            return new JsonResponse([
                'result' => true,
                'message' => 'Authenticated.',
            ]);
        }

        throw new AuthenticationException();
    }
}

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
     * @OA\Post(
     *  path="/instructor/login",
     *  tags={"Instructor-Auth"},
     *  summary="講師ログインAPI",
     *  description="講師でログインするAPIです。",
     *  @OA\RequestBody(
     *      required=true,
     *      @OA\JsonContent(
     *          required={"email","password"},
     *          @OA\Property(property="email", type="string", format="email", example="test@examle.com"),
     *          @OA\Property(property="password", type="string", format="password", example="password"),
     *      )
     *   ),
     *  @OA\Response(
     *     response=200,
     *     description="OK",
     *    @OA\JsonContent(
     *     required={"result", "message"},
     *     @OA\Property(property="result", type="boolean", example=true),
     *     @OA\Property(property="message", type="string", example="Authenticated.")
     *    )
     *  ),
     * )
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

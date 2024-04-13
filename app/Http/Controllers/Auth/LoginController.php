<?php

namespace App\Http\Controllers\Auth;

use OpenApi\Annotations as OA;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Auth\AuthenticationException;

final class LoginController extends Controller
{
    /**
     * @OA\Post(
     *  path="/login",
     *  tags={"Student-Auth"},
     *  summary="生徒ログインAPI",
     *  description="生徒でログインするAPIです。",
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
        if (Auth::attempt($request->only(['email', 'password']))) {
            $request->session()->regenerate();
            return new JsonResponse([
                'result' => true,
                'message' => 'Authenticated.',
            ]);
        }

        throw new AuthenticationException();
    }
}

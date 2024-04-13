<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogoutController extends Controller
{
    /**
     * @OA\Post(
     *  path="/logout",
     *  tags={"Student-Auth"},
     *  summary="生徒ログアウトAPI",
     *  description="生徒でログアウトするAPIです。",
     * @OA\Response(
     *     response=200,
     *     description="OK",
     *    @OA\JsonContent(
     *     required={"result", "message"},
     *     @OA\Property(property="message", type="string", example="Unauthenticated.")
     *    )
     *  ),
     * )
     * @param  \Illuminate\Http\Request  $request
     * @return JsonResponse
     */
    public function __invoke(Request $request)
    {
        if (Auth::guest()) {
            return new JsonResponse([
                'message' => 'Already Unauthenticated.',
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return new JsonResponse([
            'message' => 'Unauthenticated.',
        ]);
    }
}

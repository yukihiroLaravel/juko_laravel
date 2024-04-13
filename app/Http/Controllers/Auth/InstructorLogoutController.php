<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InstructorLogoutController extends Controller
{
    /**
     * @OA\Post(
     *  path="/instructor/logout",
     *  tags={"Instructor-Auth"},
     *  summary="講師ログアウトAPI",
     *  description="講師でログアウトするAPIです。",
     *  @OA\Response(
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
        if (Auth::guard('instructor')->guest()) {
            return new JsonResponse([
                'message' => 'Already Unauthenticated.',
            ]);
        }

        Auth::guard('instructor')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return new JsonResponse([
            'message' => 'Unauthenticated.',
        ]);
    }
}

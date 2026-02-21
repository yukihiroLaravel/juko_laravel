<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Model\Student;
use App\Model\StudentLoginHistory;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

final class LoginController extends Controller
{
    /**
     * @return JsonResponse
     *
     * @throws AuthenticationException
     */
    public function __invoke(LoginRequest $request)
    {
        if (Auth::attempt($request->only(['email', 'password']))) {
            $request->session()->regenerate();

            /** @var Student $student */
            $student = Auth::user();
            StudentLoginHistory::create([
                'student_id' => $student->id,
                'logged_in_at' => Carbon::now(),
            ]);

            return new JsonResponse([
                'result' => true,
                'message' => 'Authenticated.',
            ]);
        }

        throw new AuthenticationException;
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Model\Student;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

            try {
                DB::transaction(function () use ($student) {
                    $student->loginHistories()->create([
                        'logged_in_at' => Carbon::now(),
                    ]);
                    $student->update(['last_login_at' => Carbon::now()]);
                });
            } catch (\Throwable $e) {
                \Log::error('ログイン履歴の記録に失敗しました', [
                    'student_id' => $student->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return new JsonResponse([
                'result' => true,
                'message' => 'Authenticated.',
            ]);
        }

        throw new AuthenticationException;
    }
}

<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Student;
use App\Model\StudentAuthorization;
use App\Http\Resources\StudentEditResource;
use App\Http\Requests\Student\StudentPatchRequest;
use App\Http\Requests\Student\UserAuthenticationRequest;
use App\Http\Resources\Student\StudentPatchResource;
use App\Rules\UniqueEmailRule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;
use App\Exceptions\DuplicateAuthorizationCodeException;
use App\Exceptions\DuplicateAuthorizationTokenException;
use App\Exceptions\DuplicateAuthorizationCodeAuthException;
use App\Http\Requests\Student\StudentPostRequest;
use App\Http\Resources\Student\StudentPostResource;
use Illuminate\Support\Facades\Mail;
use App\Mail\AuthenticationConfirmationMail;

class StudentController extends Controller
{
    /**
     * ユーザー新規仮登録API
     *
     * @param StudentStoreRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StudentPostRequest $request)
   {
        DB::beginTransaction();
        try {

            $student = Student::create([
                'nick_name'  => $request->nick_name,
                'last_name'  => $request->last_name,
                'first_name' => $request->first_name,
                'email'      => $request->email,
                'occupation' => $request->occupation,
                'purpose'    => $request->purpose,
                'birth_date' => $request->birth_date,
                'sex'        => $request->sex,
                'address'    => $request->address,
            ]);

            //認証コードの生成
            $code = sprintf('%04d', mt_rand(0, 9999));

            for ($i = 1; $i <= 5; $i++) {
                if (!StudentAuthorization::where('code', $code)->exists()) {
                    break;
                }
                $code = sprintf('%04d', mt_rand(0, 9999));

                if ($i === 5) {
                    throw new DuplicateAuthorizationCodeException('Failed to generate unique authorization code.', $student);
                }
            }

            //トークンの生成
            $token = Str::random(10);
            for ($i = 1; $i <= 5; $i++) {
                if (!StudentAuthorization::where('token', $token)->exists()) {
                    break;
                }
                $token = Str::random(10);
                if ($i === 5) {
                    throw new DuplicateAuthorizationTokenException('Failed to generate unique authorization token.', $student);
                }
            }

            StudentAuthorization::create([
                'student_id'  => $student->id,
                'trial_count' => 0,
                'code'        => $code,
                'token'       => $token,
                'expire_at'   => Carbon::now()->addMinutes(60),
            ]);

            DB::commit();

            Mail::send(new AuthenticationConfirmationMail($student, $code, $token));

            return response()->json([
                'result'  => true,
                'data'    => new StudentPostResource($student),
            ]);

        } catch (DuplicateAuthorizationCodeException $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
              "result" => false,
            ], 500);

        } catch (DuplicateAuthorizationTokenException $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
            "result" => false,
            ], 500);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            return response()->json([
              "result" => false,
            ], 500);
        }
    }

    /**
     * ユーザー情報編集API
     *
     * @return StudentEditResource
     */
    public function edit(Request $request)
    {
        $student = Student::findOrFail($request->user()->id);
        return new StudentEditResource($student);
    }

    /**
     * 生徒情報更新API
     *
     * @param StudentPatchRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(StudentPatchRequest $request)
    {
        try {

            $student = Student::findOrFail($request->user()->id); 

            $request->validate([
                'email' => [new UniqueEmailRule($student->email)],
            ]);


            $student->fill([
                'nick_name' => $request->nick_name,
                'last_name' => $request->last_name,
                'first_name' => $request->first_name,
                'occupation' => $request->occupation,
                'email' => $request->email,
                'purpose' => $request->purpose,
                'birth_date' => $request->birth_date,
                'sex' => $request->sex,
                'address' => $request->address,
            ])
            ->save();

            return response()->json([
                'result' => true,
                'data' => new StudentPatchResource($student)
            ]);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'result' => false,
            ], 500);
        }
    }

    public function verifyCode(UserAuthenticationRequest $request, $token)
    {

        $code = $request->code;
        $StudentAuth = StudentAuthorization::where('token', $token)->first();
        $Student = Student::findOrFail($StudentAuth->student_id);
        $password = $request->password;
        $CurrentTime = date('Y-m-d H:i:s');

        try {
            if ($code == $StudentAuth->code) {
                // 認証成功
                $StudentAuth->delete();
                // 生徒情報を更新
                $Student->email_verified_at = $CurrentTime;
                $Student->password = bcrypt($password);
                $Student->save();
                return response()->json([
                    'result'  => true,
                ]);
            } else {
                // 認証失敗
                $StudentAuth->trial_count += 1;
                if ((strtotime($StudentAuth->expire_at) < strtotime($CurrentTime)) ||
                    ($StudentAuth->trial_count >= 3)) {
                    // 認証不成立
                    throw new DuplicateAuthorizationCodeAuthException('Failed to confirm authentication code.', $Student);
                } else {
                    $StudentAuth->save();
                }
            }
        } catch (DuplicateAuthorizationCodeAuthException $e) {
            $StudentAuth->delete();
            return response()->json([
                'result'  => false,
                'message' => "User authentication failed due to expire period or retry over 3 times.",
            ], 500);
        }
    }

}
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use App\Exceptions\DuplicateAuthorizationCodeException;
use App\Exceptions\DuplicateAuthorizationTokenException;
use App\Exceptions\ExpiredAuthorizationCodeException;
use App\Exceptions\TryCountOverAuthorizationCodeException;
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
        $password = $request->password;
        $current_time = date('Y-m-d H:i:s');

        try {
            $student_auth = StudentAuthorization::where('token', $token)->firstOrFail();
            $student = student::findOrFail($student_auth->student_id);

            // 有効期限の判定
            if (strtotime($student_auth->expire_at) < strtotime($current_time)) {
                // 有効期限切れ
                throw new ExpiredAuthorizationCodeException('Expired the period of authorization code.', $student);
            }

            // 認証コードチェック
            if ($code !== $student_auth->code) {
                // 認証失敗

                // 試行回数をカウント
                $student_auth->trial_count += 1;
                // 試行回数制限の判定
                if ($student_auth->trial_count >= 3) {
                    // 認証失敗回数が3回以上
                    throw new TryCountOverAuthorizationCodeException('The authentication failure count exceeded three times.', $student);
                } else {
                    // 試行回数を更新
                    $student_auth->update();
                    // エラー応答
                    return response()->json([
                        'result'  => false,
                        'message' => "Not match authentication code.",
                    ], 400);
                }

            } else {
                // 認証成功

                DB::beginTransaction();
                // 生徒認証情報を物理削除
                $student_auth->delete();
                // 生徒情報を更新
                $student->email_verified_at = $current_time;
                $student->password = Hash::make($password);
                $student->update();
                DB::commit();

                // 成功応答
                return response()->json([
                    'result'  => true,
                    'message' => "Authorization success.",
                ]);
            }

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'result'  => false,
                'message' => "Not Found data to match token.",
            ], 404);
        } catch (ExpiredAuthorizationCodeException $e) {
            $student_auth->delete();
            return response()->json([
                'result'  => false,
                'message' => "Expired authrization period.",
            ], 406);
        } catch (TryCountOverAuthorizationCodeException $e) {
            $student_auth->delete();
            return response()->json([
                'result'  => false,
                'message' => "Not match authrization code three times.",
            ], 400);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json([
                'result' => false,
            ], 500);
        }
    }

}
<?php

namespace App\Http\Controllers\Api\Student;

use App\Exceptions\DuplicateAuthorizationCodeException;
use App\Exceptions\DuplicateAuthorizationTokenException;
use App\Exceptions\ExpiredAuthorizationCodeException;
use App\Exceptions\TryCountOverAuthorizationCodeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudentPatchRequest;
use App\Http\Requests\Student\StudentPostRequest;
use App\Http\Requests\Student\UserAuthenticationRequest;
use App\Http\Resources\Student\StudentShowResource;
use App\Mail\AuthenticationConfirmationMail;
use App\Model\Student;
use App\Model\TemporaryStudent;
use App\Services\Student\CredentialGeneratorService;
use App\Services\Student\QueryService;
use App\Services\Student\VerifyCodeService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentController extends Controller
{
    /**
     * 生徒情報取得API
     *
     * @return StudentShowResource
     */
    public function show(Request $request, QueryService $queryService)
    {
        // 生徒情報を取得
        $student = $queryService->getStudent($request->user()->id);

        // 生徒の詳細情報をリソース形式で返す
        return new StudentShowResource($student);
    }

    /**
     * ユーザー新規仮登録API
     */
    public function store(
        StudentPostRequest $request,
        CredentialGeneratorService $credentialGeneratorService
    ): JsonResponse {
        DB::beginTransaction();
        try {
            $email = $request->email;
            $credentialGeneratorService->setEmail($email);

            $trialCount = 0;

            // 認証コードを生成する。
            $code = $credentialGeneratorService->createCode();
            // トークンを生成する。
            $token = $credentialGeneratorService->createToken();

            $expireAt = Carbon::now()->addMinutes(60);
            $nickName = $request->nick_name;
            $lastName = $request->last_name;
            $firstName = $request->first_name;
            $occupation = $request->occupation;
            $purpose = $request->purpose;
            $birthDate = $request->birth_date;
            $gender = $request->gender;
            $address = $request->address;

            /** @var TemporaryStudent $temporaryStudent */
            $temporaryStudent = TemporaryStudent::create([
                'trial_count' => $trialCount,
                'code' => $code,
                'token' => $token,
                'expire_at' => $expireAt,
                'nick_name' => $nickName,
                'last_name' => $lastName,
                'first_name' => $firstName,
                'email' => $email,
                'occupation' => $occupation,
                'purpose' => $purpose,
                'birth_date' => $birthDate,
                'gender' => $gender,
                'address' => $address,
            ]);

            DB::commit();

            Mail::send(new AuthenticationConfirmationMail($email, $temporaryStudent->fullName, $code, $token));

            return response()->json([
                'result' => true,
            ]);
        } catch (DuplicateAuthorizationCodeException $e) {
            DB::rollBack();
            Log::error($e);

            return response()->json([
                'result' => false,
                'message' => 'Failed to generate unique authorization code.',
            ], 400);
        } catch (DuplicateAuthorizationTokenException $e) {
            DB::rollBack();
            Log::error($e);

            return response()->json([
                'result' => false,
                'message' => 'Failed to generate unique authorization token.',
            ], 400);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e);
            throw $e;
        }
    }

    /**
     * 生徒情報更新API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(StudentPatchRequest $request)
    {

        $file = $request->file('profile_image');

        try {
            $student = Student::findOrFail($request->user()->id);

            if ($request->user()->id !== $student->id) {
                return response()->json([
                    'result' => 'false',
                    'message' => 'Not authorized.',
                ], 403);
            }

            $imagePath = $student->profile_image;

            if (isset($file)) {
                // 更新前の画像ファイルを削除
                if (Storage::disk('public')->exists($student->profile_image)) {
                    Storage::disk('public')->delete($student->profile_image);
                }

                // 画像ファイル保存処理
                $extension = $file->getClientOriginalExtension();
                $filename = Str::uuid()->toString().'.'.$extension;
                $imagePath = Storage::putFileAs('public/student', $file, $filename);
                $imagePath = Student::convertImagePath($imagePath);
            }

            $student->fill([
                'nick_name' => $request->nick_name,
                'last_name' => $request->last_name,
                'first_name' => $request->first_name,
                'occupation' => $request->occupation,
                'email' => $request->email,
                'purpose' => $request->purpose,
                'birth_date' => $request->birth_date,
                'gender' => $request->gender,
                'address' => $request->address,
                'profile_image' => $imagePath,
            ])
                ->save();

            return response()->json([
                'result' => true,
            ]);
        } catch (Exception $e) {
            Log::error($e);

            return response()->json([
                'result' => false,
            ], 500);
        }
    }

    /**
     * 「認証コードチェック」と「生徒の本登録」
     */
    public function verifyCode(
        UserAuthenticationRequest $request,
        VerifyCodeService $service
    ): JsonResponse {
        $token = $request->token;
        $code = $request->code;
        $currentTime = date('Y-m-d H:i:s');

        $temporaryStudent = null;
        try {
            $temporaryStudent = TemporaryStudent::where('token', $token)->firstOrFail();

            // 認証コードチェック
            try {
                $ret = $service(
                    $temporaryStudent,
                    $currentTime,
                    $code
                );
                if (! $ret) {
                    // 認証失敗

                    // エラー応答
                    return response()->json([
                        'result' => false,
                        'message' => 'Not match authentication code.',
                    ], 400);
                }
            } catch (ExpiredAuthorizationCodeException $e) {
                // 生徒仮登録認証情報を物理削除
                $temporaryStudent->delete();

                return response()->json([
                    'result' => false,
                    'message' => 'Expired authorization period.',
                ], 400);
            } catch (TryCountOverAuthorizationCodeException $e) {
                // 生徒仮登録認証情報を物理削除
                $temporaryStudent->delete();

                return response()->json([
                    'result' => false,
                    'message' => 'Not match authorization code three times.',
                ], 400);
            }

            // 認証成功
            DB::transaction(function () use (
                $request,
                $temporaryStudent
            ) {
                $password = $request->password;

                // 生徒の本登録
                $student = Student::create([
                    'given_name_by_instructor' => null,
                    'nick_name' => $temporaryStudent->nick_name,
                    'last_name' => $temporaryStudent->last_name,
                    'first_name' => $temporaryStudent->first_name,
                    'occupation' => $temporaryStudent->occupation,
                    'email' => $temporaryStudent->email,
                    'password' => Hash::make($password),
                    'purpose'=> $temporaryStudent->purpose,
                    'birth_date'=> $temporaryStudent->birth_date,
                    'gender' => $temporaryStudent->gender,
                    'address' => $temporaryStudent->address,
                    'profile_image' => null,
                ]);

                // 生徒仮登録認証情報を物理削除
                $temporaryStudent->delete();
            });

            // 成功応答
            return response()->json([
                'result' => true,
                'message' => 'Authorization success.',
            ]);
        } catch (Exception $e) {
            Log::error($e);
            throw $e;
        }
    }
}

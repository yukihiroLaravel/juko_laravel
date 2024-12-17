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
use App\Services\Student\QueryService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
     *
     * @return \Illuminate\Http\JsonResponse
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

    public function verifyCode(UserAuthenticationRequest $request): JsonResponse
    {

        $code = $request->code;
        $password = $request->password;
        $currentTime = date('Y-m-d H:i:s');

        try {
            $studentAuth = StudentAuthorization::where('token', $request->token)->firstOrFail();
            $student = student::findOrFail($studentAuth->student_id);

            // 有効期限の判定
            if (strtotime($studentAuth->expire_at) < strtotime($currentTime)) {
                // 有効期限切れ
                throw new ExpiredAuthorizationCodeException('Expired the period of authorization code.', $student->email);
            }

            // 認証コードチェック
            if ($code !== $studentAuth->code) {
                // 認証失敗

                // 試行回数をカウント
                $studentAuth->trial_count += 1;
                // 試行回数制限の判定
                if ($studentAuth->trial_count >= 3) {
                    // 認証失敗回数が3回以上
                    throw new TryCountOverAuthorizationCodeException('The authentication failure count exceeded three times.', $student->email);
                }

                // 試行回数を更新
                $studentAuth->update();

                // エラー応答
                return response()->json([
                    'result' => false,
                    'message' => 'Not match authentication code.',
                ], 400);
            }

            // 認証成功
            DB::beginTransaction();
            // 生徒認証情報を物理削除
            $studentAuth->delete();
            // 生徒情報を更新
            $student->email_verified_at = $currentTime;
            $student->password = Hash::make($password);
            $student->update();
            DB::commit();

            // 成功応答
            return response()->json([
                'result' => true,
                'message' => 'Authorization success.',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'result' => false,
                'message' => 'Not Found data to match token.',
            ], 404);
        } catch (ExpiredAuthorizationCodeException $e) {
            $studentAuth->delete();

            return response()->json([
                'result' => false,
                'message' => 'Expired authorization period.',
            ], 406);
        } catch (TryCountOverAuthorizationCodeException $e) {
            $studentAuth->delete();

            return response()->json([
                'result' => false,
                'message' => 'Not match authorization code three times.',
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

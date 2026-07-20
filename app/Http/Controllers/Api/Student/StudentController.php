<?php

namespace App\Http\Controllers\Api\Student;

use App\Exceptions\DuplicateAuthorizationCodeException;
use App\Exceptions\DuplicateAuthorizationTokenException;
use App\Exceptions\ExpiredAuthorizationCodeException;
use App\Exceptions\TryCountOverAuthorizationCodeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreRequest;
use App\Http\Requests\Student\UpdateRequest;
use App\Http\Requests\Student\UserAuthenticationRequest;
use App\Http\Resources\Student\StudentShowResource;
use App\Mail\AuthenticationConfirmationMail;
use App\Model\Student;
use App\Model\TemporaryStudent;
use App\Services\Auth\CredentialGeneratorService;
use App\Services\Student\QueryService;
use App\Services\Student\VerifyCodeService;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @tags Student
 */
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
        StoreRequest $request,
        CredentialGeneratorService $credentialGeneratorService
    ): JsonResponse {
        $email = $request->email;
        DB::beginTransaction();
        try {

            // 認証コードを生成する。
            $code = $credentialGeneratorService->createCode(
                existsChecker: fn (string $code) => TemporaryStudent::where('code', $code)->exists(),
            );

            // トークンを生成する。
            $token = $credentialGeneratorService->createToken(
                existsChecker: fn (string $token) => TemporaryStudent::where('token', $token)->exists(),
            );

            $temporaryStudent = TemporaryStudent::create([
                'trial_count' => 0,
                'code' => $code,
                'token' => $token,
                'expire_at' => Carbon::now()->addMinutes(60),
                'nick_name' => $request->nick_name,
                'last_name' => $request->last_name,
                'first_name' => $request->first_name,
                'email' => $email,
                'occupation' => $request->occupation,
                'purpose' => $request->purpose,
                'birth_date' => $request->birth_date,
                'gender' => $request->gender,
                'address' => $request->address,
            ]);

            DB::commit();

            Mail::send(new AuthenticationConfirmationMail($email, $temporaryStudent->full_name, $code, $token));

            return response()->json([
                'result' => true,
            ]);
        } catch (DuplicateAuthorizationCodeException $e) {
            DB::rollBack();
            Log::error($e->getMessage().' email: '.$request->email);

            return response()->json([
                'result' => false,
                'message' => 'Failed to generate unique authorization code.',
            ], 400);
        } catch (DuplicateAuthorizationTokenException $e) {
            DB::rollBack();
            Log::error($e->getMessage().' email: '.$request->email);

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
     * @return JsonResponse
     */
    public function update(UpdateRequest $request)
    {

        $file = $request->file('profile_image');

        try {
            $student = Student::findOrFail($request->user()->id);

            if ($request->user()->id !== $student->id) {
                throw new AuthorizationException('Not authorized.');
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
            throw $e;
        }
    }

    /**
     * 認証コード検証API
     */
    public function verifyCode(
        UserAuthenticationRequest $request,
        VerifyCodeService $service
    ): JsonResponse {
        $token = $request->token;

        try {
            $temporaryStudent = TemporaryStudent::where('token', $token)->firstOrFail();

            // 認証コードチェック
            try {
                $result = $service(
                    temporaryStudent: $temporaryStudent,
                    currentTime: CarbonImmutable::now(),
                    code: $request->code
                );
                if (! $result) {
                    // 認証コード不一致
                    return response()->json([
                        'result' => false,
                        'message' => 'Not match authentication code.',
                    ], 400);
                }
            } catch (ExpiredAuthorizationCodeException) {
                // 仮登録情報を物理削除
                $temporaryStudent->delete();

                return response()->json([
                    'result' => false,
                    'message' => 'Expired authorization period.',
                ], 400);
            } catch (TryCountOverAuthorizationCodeException) {
                // 仮登録情報を物理削除
                $temporaryStudent->delete();

                return response()->json([
                    'result' => false,
                    'message' => 'Not match authorization code three times.',
                ], 400);
            }

            // 認証成功

            /*
                トランザクションの範囲を限定する理由:
                1) VerifyCodeService内での試行回数カウントのDB更新
                2) VerifyCodeServiceでの例外発生時の仮登録情報削除
                上記はトランザクション外で実行したい。
                本登録に関する処理のみトランザクション内で実行するため、
                DB::transaction(function () use (...) で自動コミット/ロールバックを利用。
            */
            DB::transaction(function () use (
                $temporaryStudent,
                $request
            ) {
                // 生徒の本登録
                $student = Student::create([
                    'given_name_by_instructor' => null,
                    'nick_name' => $temporaryStudent->nick_name,
                    'last_name' => $temporaryStudent->last_name,
                    'first_name' => $temporaryStudent->first_name,
                    'occupation' => $temporaryStudent->occupation,
                    'email' => $temporaryStudent->email,
                    'password' => Hash::make($request->password),
                    'purpose' => $temporaryStudent->purpose,
                    'birth_date' => $temporaryStudent->birth_date,
                    'gender' => $temporaryStudent->gender,
                    'address' => $temporaryStudent->address,
                    'profile_image' => null,
                ]);

                // 仮登録情報を物理削除
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

<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Exceptions\DuplicateAuthorizationCodeException;
use App\Exceptions\DuplicateAuthorizationTokenException;
use App\Exceptions\ExpiredAuthorizationCodeException;
use App\Exceptions\TryCountOverAuthorizationCodeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\StoreRequest;
use App\Http\Requests\Instructor\UpdateRequest;
use App\Http\Requests\Instructor\UserAuthenticationRequest;
use App\Http\Resources\Base\Instructor\InstructorResource;
use App\Mail\AuthenticationConfirmationMail;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use App\Model\TemporaryInstructor;
use App\Services\Auth\CredentialGeneratorService;
use App\Services\Instructor\StoreService;
use App\Services\Instructor\VerifyCodeService;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @tags Instructor
 */
class InstructorController extends Controller
{
    /**
     * 講師取得API
     */
    public function show(): InstructorResource
    {
        $instructor = Instructor::findOrFail(Auth::guard('instructor')->user()->id);

        return new InstructorResource($instructor);
    }

    /**
     * 講師用 仮登録API
     */
    public function store(
        StoreRequest $request,
        StoreService $storeService,
        CredentialGeneratorService $credentialGeneratorService
    ): JsonResponse {
        $email = $request->email;

        DB::beginTransaction();
        try {
            // 認証コードを生成する
            $code = $credentialGeneratorService->createCode(
                existsChecker: fn (string $code) => TemporaryInstructor::where('code', $code)->exists(),
            );

            // トークンを生成する
            $token = $credentialGeneratorService->createToken(
                existsChecker: fn (string $token) => TemporaryInstructor::where('token', $token)->exists(),
            );

            //サービスクラス呼び出し
            $temporaryInstructor = $storeService(
                code: $code,
                token: $token,
                data: [
                    'email' => $email,
                    'nick_name' => $request->nick_name,
                    'last_name' => $request->last_name,
                    'first_name' => $request->first_name,
                ],
                managerId: null,
            );

            DB::commit();

            //送信
            Mail::send(new AuthenticationConfirmationMail(
                $email,
                $temporaryInstructor->full_name,
                $code,
                $token
            ));

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
     * 講師更新API
     */
    public function update(UpdateRequest $request): JsonResponse
    {
        try {
            $instructor = Auth::user();

            // 更新前の画像パスを使用
            $imagePath = $instructor->profile_image;
            $file = $request->file('profile_image');

            if (isset($file)) {
                // 更新前の画像ファイルを削除
                if (Storage::disk('public')->exists($instructor->profile_image)) {
                    Storage::disk('public')->delete($instructor->profile_image);
                }

                // 画像ファイル保存処理
                $extension = $file->getClientOriginalExtension();
                $filename = Str::uuid()->toString().'.'.$extension;
                $imagePath = Storage::disk('public')->putFileAs('instructor', $file, $filename);
            }

            $instructor->update([
                'nick_name' => $request->nick_name,
                'last_name' => $request->last_name,
                'first_name' => $request->first_name,
                'email' => $request->email,
                'profile_image' => $imagePath,
            ]);

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
            $temporaryInstructor = TemporaryInstructor::where('token', $token)->firstOrFail();

            // 認証コードチェック
            try {
                $result = $service(
                    temporaryInstructor: $temporaryInstructor,
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
                $temporaryInstructor->delete();

                return response()->json([
                    'result' => false,
                    'message' => 'Expired authorization period.',
                ], 400);
            } catch (TryCountOverAuthorizationCodeException) {
                // 仮登録情報を物理削除
                $temporaryInstructor->delete();

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
                $request,
                $temporaryInstructor
            ) {
                // 講師の本登録
                $instructor = Instructor::create([
                    'nick_name' => $temporaryInstructor->nick_name,
                    'last_name' => $temporaryInstructor->last_name,
                    'first_name' => $temporaryInstructor->first_name,
                    'email' => $temporaryInstructor->email,
                    'password' => Hash::make($request->password),
                    'profile_image' => null,
                    'type' => $temporaryInstructor->type,
                ]);
                assert($instructor instanceof Instructor);

                if ($temporaryInstructor->manager_id) {
                    /*
                    manager_idが存在する場合、仮登録を行ったマネージャの配下に
                    本登録された講師を紐づけるため、manage_instructorsにデータを登録する。
                    */
                    ManageInstructor::create([
                        'instructor_id' => $instructor->id,
                        'manager_id' => $temporaryInstructor->manager_id,
                    ]);
                }

                // 仮登録情報を物理削除
                $temporaryInstructor->delete();
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

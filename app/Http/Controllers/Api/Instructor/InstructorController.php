<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Exceptions\DuplicateAuthorizationCodeException;
use App\Exceptions\DuplicateAuthorizationTokenException;
use App\Exceptions\ExpiredAuthorizationCodeException;
use App\Exceptions\TryCountOverAuthorizationCodeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\InstructorPatchRequest;
use App\Http\Requests\Instructor\InstructorPostRequest;
use App\Http\Requests\Instructor\UserAuthenticationRequest;
use App\Http\Resources\Instructor\InstructorShowResource;
use App\Mail\AuthenticationConfirmationMail;
use App\Model\Instructor;
use App\Model\ManageInstructor;
use App\Model\TemporaryInstructor;
use App\Services\Instructor\CredentialGeneratorService;
use App\Services\Instructor\QueryService;
use App\Services\Instructor\VerifyCodeService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class InstructorController extends Controller
{
    /**
     * 講師取得API
     *
     * @return InstructorShowResource
     */
    public function show(QueryService $queryService)
    {
        /** @var Instructor $instructor */
        $instructor = $queryService->getInstructor(Auth::guard('instructor')->user()->id);

        return new InstructorShowResource($instructor);
    }

    /**
     * ユーザー新規仮登録API
     */
    public function store(
        InstructorPostRequest $request,
        CredentialGeneratorService $credentialGeneratorService
    ): JsonResponse {
        DB::beginTransaction();
        try {
            $email = $request->email;
            $credentialGeneratorService->setEmail($email);

            // 登録対象の講師が自分で仮登録するケースなので、$managerIdはnull固定
            $managerId = null;

            $trialCount = 0;

            // 認証コードを生成する。
            $code = $credentialGeneratorService->createCode();
            // トークンを生成する。
            $token = $credentialGeneratorService->createToken();

            $expireAt = Carbon::now()->addMinutes(60);
            $nickName = $request->nick_name;
            $lastName = $request->last_name;
            $firstName = $request->first_name;

            /*
               講師の新規登録時に'manager'として登録のケースは、
               フロントエンドからrequestBodyに、typeに、Instructor::TYPE_MANAGERを指定すればよい。
               そうでなければ、requestBodyに、typeを指定する必要もなく、その際はnullであり、
               そのケースでは、Instructor::TYPE_INSTRUCTORとすることを意図している。
             */
            $type = $request->filled('type') ? $request->type : Instructor::TYPE_INSTRUCTOR;

            $temporaryInstructor = TemporaryInstructor::create([
                'manager_id' => $managerId,
                'trial_count' => $trialCount,
                'code' => $code,
                'token' => $token,
                'expire_at' => $expireAt,
                'nick_name' => $nickName,
                'last_name' => $lastName,
                'first_name' => $firstName,
                'email' => $email,
                'type' => $type,
            ]);

            DB::commit();

            Mail::send(new AuthenticationConfirmationMail($email, $temporaryInstructor->fullName, $code, $token));

            return response()->json([
                'result' => true,
            ]);
        } catch (DuplicateAuthorizationCodeException $e) {
            DB::rollBack();
            Log::error($e);

            return response()->json([
                'result' => false,
            ], 400);
        } catch (DuplicateAuthorizationTokenException $e) {
            DB::rollBack();
            Log::error($e);

            return response()->json([
                'result' => false,
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
    public function update(InstructorPatchRequest $request): JsonResponse
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
        } catch (RuntimeException $e) {
            Log::error($e);

            return response()->json([
                'result' => false,
            ], 500);
        }
    }

    /**
     * 「認証コードチェック」と「講師の本登録」
     */
    public function verifyCode(
        UserAuthenticationRequest $request,
        VerifyCodeService $service
    ): JsonResponse {
        $token = $request->token;
        $code = $request->code;
        $password = $request->password;
        $currentTime = date('Y-m-d H:i:s');

        $isTransaction = false;
        $temporaryInstructor = null;
        try {
            $temporaryInstructor = TemporaryInstructor::where('token', $token)->firstOrFail();

            // 認証コードチェック
            $ret = $service(
                $temporaryInstructor,
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

            // 認証成功
            DB::beginTransaction();
            $isTransaction = true;

            // 講師の本登録
            $instructor = Instructor::create([
                'nick_name' => $temporaryInstructor->nick_name,
                'last_name' => $temporaryInstructor->last_name,
                'first_name' => $temporaryInstructor->first_name,
                'email' => $temporaryInstructor->email,
                'password' => Hash::make($password),
                'profile_image' => null,
                'type' => $temporaryInstructor->nick_name,
            ]);

            if ($temporaryInstructor->manager_id) {
                /*
                   manager_idが値ある場合、つまり、「講師の仮登録の操作」をマネージャが行った場合に相当する。

                   この場合は、マネージャが配下の講師を仮登録した後、
                   仮登録された講師の本人が自分宛てに来たメールの本文にある
                   トークンが含まれたurlから表示したページで認証コードを入力し、当APIを動作させた状況である。

                   その結果として、認証に成功し、その講師が本登録処理され、当ロジックに至ったケースに相当する。

                   そのため、仮登録の操作を行ったマネージャの配下に、今、本登録された講師を紐づけるための
                   manage_instructorsへのデータ登録を行う。

                   $temporaryInstructor->manager_idには、仮登録の操作を行ったマネージャのinstructorsテーブルのid項目値
                   $instructor->idには、今、本登録された講師のinstructorsテーブルのid項目値
                   の値になっている状況を想定し、
                   下記のmanage_instructorsへのデータ登録を行う。
                 */
                $manageInstructor = ManageInstructor::create([
                    'instructor_id' => $instructor->id,
                    'manager_id' => $temporaryInstructor->manager_id,
                ]);
            }

            // 講師仮登録認証情報を物理削除
            $temporaryInstructor->delete();

            DB::commit();
            $isTransaction = false;

            // 成功応答
            return response()->json([
                'result' => true,
                'message' => 'Authorization success.',
            ]);
        } catch (ModelNotFoundException $e) {
            if ($isTransaction) {
                DB::rollback();
                $isTransaction = false;
            }
            Log::error($e);
            throw $e;
        } catch (ExpiredAuthorizationCodeException $e) {
            if ($isTransaction) {
                DB::rollback();
                $isTransaction = false;
            }
            if ($temporaryInstructor) {
                // 講師仮登録認証情報を物理削除
                $temporaryInstructor->delete();
            }

            return response()->json([
                'result' => false,
                'message' => 'Expired authrization period.',
            ], 400);
        } catch (TryCountOverAuthorizationCodeException $e) {
            if ($isTransaction) {
                DB::rollback();
                $isTransaction = false;
            }
            if ($temporaryInstructor) {
                // 講師仮登録認証情報を物理削除
                $temporaryInstructor->delete();
            }

            return response()->json([
                'result' => false,
                'message' => 'Not match authrization code three times.',
            ], 400);
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e);
            throw $e;
        }
    }
}

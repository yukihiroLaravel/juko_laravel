<?php

namespace App\Services\Instructor;

use App\Mail\AuthenticationConfirmationMail;
use App\Model\TemporaryInstructor;
use App\Model\Instructor;
use App\Services\Auth\CredentialGeneratorService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * 仮講師登録サービス
 *
 * 認証コードとトークンを生成し、TemporaryInstructorレコードを作成。
 * 成功後、認証確認メールを送信する。
 *
 * @param  string  $email  仮講師のメールアドレス
 * @param  string  $nickName  仮講師のニックネーム
 * @param  string  $lastName  仮講師の姓
 * @param  string  $firstName  仮講師の名
 * @param  CredentialGeneratorService  $credentialGeneratorService  認証コード/トークン生成サービス
 * @param  int|null  $managerId  管理者ID（未指定ならnull）
 * @return void
 *
 * @throws \Exception  登録中にエラーが発生した場合
 */
class StoreService
{
    public function __invoke(
        string $email,
        string $nickName,
        string $lastName,
        string $firstName,
        CredentialGeneratorService $credentialGeneratorService,
        ?int $managerId = null // ← manager_id をオプションに
    ): void {
        DB::beginTransaction();
        try {
            // 認証コードを生成
            $code = $credentialGeneratorService->createCode(
                existsChecker: fn(string $code) => TemporaryInstructor::where('code', $code)->exists(),
            );

            // トークンを生成
            $token = $credentialGeneratorService->createToken(
                existsChecker: fn(string $token) => TemporaryInstructor::where('token', $token)->exists(),
            );

            $temporaryInstructor = TemporaryInstructor::create([
                'manager_id' => $managerId,
                'trial_count' => 0,
                'code' => $code,
                'token' => $token,
                'expire_at' => Carbon::now()->addMinutes(60),
                'nick_name' => $nickName,
                'last_name' => $lastName,
                'first_name' => $firstName,
                'email' => $email,
                'type' => Instructor::TYPE_INSTRUCTOR,
            ]);

            assert($temporaryInstructor instanceof TemporaryInstructor);

            DB::commit();

            Mail::send(new AuthenticationConfirmationMail(
                $email,
                $temporaryInstructor->full_name,
                $code,
                $token
            ));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage() . ' email: ' . $email);
            throw $e;
        }
    }
}

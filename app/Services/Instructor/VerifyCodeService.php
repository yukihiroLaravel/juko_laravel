<?php

namespace App\Services\Instructor;

use App\Model\TemporaryInstructor;
use App\Exceptions\ExpiredAuthorizationCodeException;
use App\Exceptions\TryCountOverAuthorizationCodeException;

/**
 * 認証コードチェックサービス
 */
class VerifyCodeService
{
    /**
     * インボーカブル(Invokable)メソッド
     *
     * @param TemporaryInstructor $temporaryInstructor 講師仮登録認証情報
     * @param string $currentTime 現在日時のタイムスタンプ( date('Y-m-d H:i:s')の値を想定 )
     * @param string $code 認証コード
     * @return boolean 認証コードがマッチするかどうか
     * @throws ExpiredAuthorizationCodeException 認証コードが有効期限切れの場合
     * @throws TryCountOverAuthorizationCodeException 試行回数が上限を超えた場合
     */
    public function __invoke(
        TemporaryInstructor $temporaryInstructor,
        string $currentTime,
        string $code
    ): bool {
        $email = $temporaryInstructor->email;

        // 有効期限の判定
        if (strtotime($temporaryInstructor->expire_at) < strtotime($currentTime)) {
            // 有効期限切れ
            throw new ExpiredAuthorizationCodeException('Expired the period of authorization code.', $email);
        }

        // 認証コードチェック
        if ($code !== $temporaryInstructor->code) {
            // 認証失敗

            // 試行回数をカウント
            $temporaryInstructor->trial_count += 1;
            // 試行回数制限の判定
            if ($temporaryInstructor->trial_count >= 3) {
                // 認証失敗回数が3回以上
                throw new TryCountOverAuthorizationCodeException('The authentication failure count exceeded three times.', $email);
            }

            // 試行回数を更新
            $temporaryInstructor->update();

            return false;
        }

        return true;
    }
}

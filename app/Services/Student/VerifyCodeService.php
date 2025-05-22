<?php

namespace App\Services\Student;

use App\Exceptions\ExpiredAuthorizationCodeException;
use App\Exceptions\TryCountOverAuthorizationCodeException;
use App\Model\TemporaryStudent;
use Carbon\CarbonImmutable;

/**
 * 認証コードチェックサービス
 */
class VerifyCodeService
{
    public function __invoke(
        TemporaryStudent $temporaryStudent,
        CarbonImmutable $currentTime,
        string $code
    ): bool {
        if ($temporaryStudent->expire_at->lessThan($currentTime)) {
            // 有効期限切れの場合
            throw new ExpiredAuthorizationCodeException('Expired the period of authorization code.');
        }

        if ($code !== $temporaryStudent->code) {
            $temporaryStudent->trial_count += 1;
            if ($temporaryStudent->trial_count > 3) {
                // 認証失敗回数が3回より多い場合
                throw new TryCountOverAuthorizationCodeException('The authentication failure count exceeded three times.');
            }

            // 試行回数を更新
            $temporaryStudent->update();

            return false;
        }

        return true;
    }
}

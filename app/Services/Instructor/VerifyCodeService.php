<?php

namespace App\Services\Instructor;

use App\Exceptions\ExpiredAuthorizationCodeException;
use App\Exceptions\TryCountOverAuthorizationCodeException;
use App\Model\TemporaryInstructor;
use Carbon\CarbonImmutable;

/**
 * 認証コードチェックサービス
 */
class VerifyCodeService
{
    public function __invoke(
        TemporaryInstructor $temporaryInstructor,
        CarbonImmutable $currentTime,
        string $code
    ): bool {
        if ($temporaryInstructor->expire_at->lessThan($currentTime)) {
            // 有効期限切れの場合
            throw new ExpiredAuthorizationCodeException('Expired the period of authorization code.');
        }

        if ($code !== $temporaryInstructor->code) {
            $temporaryInstructor->trial_count += 1;
            if ($temporaryInstructor->trial_count > 3) {
                // 認証失敗回数が3回より多い場合
                throw new TryCountOverAuthorizationCodeException('The authentication failure count exceeded three times.');
            }

            $temporaryInstructor->update();

            return false;
        }

        return true;
    }
}

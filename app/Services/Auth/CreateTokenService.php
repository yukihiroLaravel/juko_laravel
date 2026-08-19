<?php

namespace App\Services\Auth;

use App\Exceptions\DuplicateAuthorizationTokenException;
use Illuminate\Support\Str;

/**
 * トークン生成サービス
 */
class CreateTokenService
{
    /**
     * トークンを生成する。
     *
     * @param  callable(string): bool  $existsChecker  重複チェック関数
     */
    public function __invoke(callable $existsChecker, ?callable $randomGenerator = null): string
    {
        // トークンの生成
        $randomGenerator = $randomGenerator ?: fn () => Str::random(10);

        $token = $randomGenerator();
        for ($i = 1; $i <= 5; $i++) {
            $isExists = $existsChecker($token);
            if (! $isExists) {
                break;
            }
            $token = $randomGenerator();
            if ($i === 5) {
                throw new DuplicateAuthorizationTokenException('Failed to generate unique authorization token.');
            }
        }

        return $token;
    }
}

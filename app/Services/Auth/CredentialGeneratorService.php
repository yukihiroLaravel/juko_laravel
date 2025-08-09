<?php

namespace App\Services\Auth;

use App\Exceptions\DuplicateAuthorizationCodeException;
use App\Exceptions\DuplicateAuthorizationTokenException;
use Illuminate\Support\Str;

/**
 * 認証情報生成サービス
 */
class CredentialGeneratorService
{
    /**
     * 認証コードを生成する。
     *
     * @param  callable(string): bool  $existsChecker  重複チェック関数
     */
    public function createCode(callable $existsChecker, ?callable $randomGenerator = null): string
    {
        $randomGenerator = $randomGenerator ?: fn () => Str::random(4);

        // 認証コードの生成
        $code = $randomGenerator(4);

        for ($i = 1; $i <= 5; $i++) {
            $isExists = $existsChecker($code);
            if (! $isExists) {
                break;
            }
            $code = $randomGenerator();

            if ($i === 5) {
                throw new DuplicateAuthorizationCodeException('Failed to generate unique authorization code.');
            }
        }

        return $code;
    }

    /**
     * トークンを生成する。
     *
     * @param  callable  $existsChecker  重複チェック関数
     */
    public function createToken(callable $existsChecker, ?callable $randomGenerator = null): string
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

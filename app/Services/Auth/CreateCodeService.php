<?php

namespace App\Services\Auth;

use App\Exceptions\DuplicateAuthorizationCodeException;
use Illuminate\Support\Str;

/**
 * 認証コード生成サービス
 */
class CreateCodeService
{
    /**
     * 認証コードを生成する。
     *
     * @param  callable(string): bool  $existsChecker  重複チェック関数
     */
    public function __invoke(callable $existsChecker, ?callable $randomGenerator = null): string
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
}

<?php

namespace App\Services\Auth;

use App\Exceptions\DuplicateAuthorizationCodeException;
use App\Exceptions\DuplicateAuthorizationTokenException;
use App\Model\TemporaryInstructor;
use Illuminate\Support\Str;

/**
 * 認証情報生成サービス
 */
class CredentialGeneratorService
{
    /**
     * 認証コードを生成する。
     */
    public function createCode(?callable $randomGenerator = null): string
    {
        $randomGenerator = $randomGenerator ?: function () {
            return Str::random(4);
        };

        //認証コードの生成
        $code = $randomGenerator(4);

        for ($i = 1; $i <= 5; $i++) {
            $isExists = TemporaryInstructor::where('code', $code)->exists();
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
     */
    public function createToken(?callable $randomGenerator = null): string
    {
        //トークンの生成
        $randomGenerator = $randomGenerator ?: function () {
            return Str::random(10);
        };
        $token = $randomGenerator();
        for ($i = 1; $i <= 5; $i++) {
            $isExists = TemporaryInstructor::where('token', $token)->exists();
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

<?php

namespace App\Services\Instructor;

use App\Exceptions\DuplicateAuthorizationCodeException;
use App\Exceptions\DuplicateAuthorizationTokenException;
use App\Model\TemporaryInstructor;
use Exception;
use Illuminate\Support\Str;

/**
 * 認証情報生成サービス
 */
class CredentialGeneratorService
{
    /** @var string */
    private $email;

    /**
     * emailを設定する。
     *
     * @return void
     */
    public function setEmail(string $email)
    {
        $this->email = $email;
    }

    /**
     * 認証コードを生成する。
     */
    public function createCode(): string
    {

        if (! $this->email) {
            throw new Exception('email is empty. at createCode()');
        }

        //認証コードの生成
        $code = sprintf('%04d', mt_rand(0, 9999));

        for ($i = 1; $i <= 5; $i++) {
            $isExists = TemporaryInstructor::where('code', $code)->exists();
            if (! $isExists) {
                break;
            }
            $code = sprintf('%04d', mt_rand(0, 9999));

            if ($i === 5) {
                throw new DuplicateAuthorizationCodeException('Failed to generate unique authorization code.', $this->email);
            }
        }

        return $code;
    }

    /**
     * トークンを生成する。
     */
    public function createToken(): string
    {

        if (! $this->email) {
            throw new Exception('email is empty. at createToken()');
        }

        //トークンの生成
        $token = Str::random(10);
        for ($i = 1; $i <= 5; $i++) {
            $isExists = TemporaryInstructor::where('token', $token)->exists();
            if (! $isExists) {
                break;
            }
            $token = Str::random(10);
            if ($i === 5) {
                throw new DuplicateAuthorizationTokenException('Failed to generate unique authorization token.', $this->email);
            }
        }

        return $token;
    }
}

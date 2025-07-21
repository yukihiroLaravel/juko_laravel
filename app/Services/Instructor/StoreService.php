<?php

namespace App\Services\Instructor;

use App\Model\TemporaryInstructor;
use Carbon\Carbon;

class StoreService
{
    /**
     * 仮講師の保存に必要な配列を生成
     *
     * @param array{
     *     email: string,
     *     nick_name: string,
     *     last_name: string,
     *     first_name: string,
     *     type: string
     * } $data
     */
    public function __invoke(
        string $code,
        string $token,
        array $data,
        ?int $managerId = null
    ): TemporaryInstructor {
        return TemporaryInstructor::create([
            'manager_id' => $managerId,
            'trial_count' => 0,
            'code' => $code,
            'token' => $token,
            'expire_at' => Carbon::now()->addMinutes(60),
            'nick_name' => $data['nick_name'],
            'last_name' => $data['last_name'],
            'first_name' => $data['first_name'],
            'email' => $data['email'],
            'type' => $data['type'],
        ]);
    }
}

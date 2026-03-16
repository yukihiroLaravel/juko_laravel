<?php

namespace App\Services\Instructor;

use App\Model\Instructor;

class InstructorCapacityService
{
    /**
     * 講師のcapacity_totalを計算する
     */
    public function calculate(Instructor $instructor): ?int //整数型を宣言
    {
        // DBから直接取得
        $cources = $instructor->courses()->get();

        // 1件でもcapacityがnullならnullを返す
        if ($cources->contains(fn ($cources) => is_null($cources->capacity))) {
            return null;
        }

        // 全て設定されていれば合計
        return $cources->sum('capacity');
    }
}
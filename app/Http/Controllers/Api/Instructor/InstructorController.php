<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Model\Instructor;
use App\Http\Resources\Instructor\InstructorEditResource;
use App\Http\Controllers\Controller;

class InstructorController extends Controller
{
     /**
     * 講師情報編集API
     *
     * @return InstructorEditResource
     */
    public function edit()
    {   
        // TODO 認証機能ができるまで、講師IDを固定値で設定
        $InstructorId = 1;
        $Instructor = Instructor::findOrFail($InstructorId);
        return new InstructorEditResource($Instructor);
    }
}

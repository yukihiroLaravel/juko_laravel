<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Model\instructor;
use App\Http\Resources\Instructor\InstructorEditResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InstructorController extends Controller
{
     /**
     * 講師情報編集API
     *
     * @param 
     * @return CourseEditResource
     */
    public function edit()
    {   
        // TODO 認証機能ができるまで、講師IDを固定値で設定
        $instructorId = 1;
        $Instructor = Instructor::findOrFail($instructorId);
        return new InstructorEditResource($Instructor);
    }
}

<?php

namespace App\Http\Controllers\Api\Student;

use Illuminate\Http\Request;
use App\Model\Student;
use App\Http\Controllers\Controller;


class StudentController extends Controller
{
    /**
     * チャプター更新API
     *
     * @param InstructorPatchRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        return response()->json([
        ]);
    }
}

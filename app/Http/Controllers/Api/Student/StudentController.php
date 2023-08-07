<?php

namespace App\Http\Controllers\Api\Student;

use Illuminate\Http\Request;
use App\Model\Student;
use App\Http\Controllers\Controller;


class StudentController extends Controller
{
    /**
     * 学生情報更新API
     *
     * @param StudentPatchRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        return response()->json([
        ]);
    }
}

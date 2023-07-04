<?php

namespace App\Http\Controllers\Api\Instructor;

use Illuminate\Http\Request;
use App\Model\Instructor;
use App\Http\Controllers\Controller;

class InstructorController extends Controller
{
    /**
     * チャプター更新API
     *
     * @param InstructorPatchRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($request)
    {
        return response()->json([]);
    }
}

<?php

namespace App\Http\Controllers\Api\Manager;

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
        return response()->json([]);
    }
}

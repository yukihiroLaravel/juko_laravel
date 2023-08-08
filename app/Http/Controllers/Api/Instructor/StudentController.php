<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    /**
     * 受講生登録API
     *
     * @param Request $request
     * @return Resource
     */

    public function store(Request $request)
    {
        return response()->json([]);
    }
}

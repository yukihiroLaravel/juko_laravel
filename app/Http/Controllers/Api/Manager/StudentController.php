<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Student;
use App\Model\Instructor;

class StudentController extends Controller
{
    /**マネージャ講座の受講生取得API
     * 
     */
    public function index() 
    {
        return response()->json([]);
    }
}

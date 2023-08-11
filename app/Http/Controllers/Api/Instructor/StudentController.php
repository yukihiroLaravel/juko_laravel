<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Student;
use Carbon\Carbon;

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
        $student = null;
        if ( Student::where('email', $request->email)->first() === null ) {
            $student = Student::create([
                // 'given_name_by_instructor' => $request->name, まだ実装されてないのでnick_nameで代用
                'nick_name' => $request->nick_name,
                'email' => $request->email,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        } else {
            return response()->json([
                'result' => false,
                "error_message" => "Invalid email.",
                "error_code" => "400"
            ]);
        }
        
        return response()->json([
            'result' => true,
            'data' => [
                'id' => $student->id,
                // $student->given_name_by_instructor, まだ実装されてないのでnick_nameで代用
                'nick_name' => $student->nick_name,
                'email' => $student->email
            ]
        ]);
    }
}
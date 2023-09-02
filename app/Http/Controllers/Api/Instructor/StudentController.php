<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;

class StudentController extends Controller
{
    public function sendMail(Request $request)
    {
      $name = 'ユーザー１';
      $email = 'user_1@test.com';
  
      Mail::send(new TestMail($name,$email));
      return response()->json(['message'=>'テストメールが送信されました',]);
  
    }
}

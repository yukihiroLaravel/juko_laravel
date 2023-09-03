<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; //postman確認のため仮作成

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([]);
    }
}

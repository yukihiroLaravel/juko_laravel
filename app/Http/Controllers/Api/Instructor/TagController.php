<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * 分類表示ボタン活性の時の講座一覧API
     */
    public function index()
    {
        return response()->json([]);
    }
}

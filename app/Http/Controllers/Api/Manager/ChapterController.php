<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChapterController extends Controller
{
 /**
 * マネージャ講座 管理下講師のチャプター情報を取得
 */
 public function index() {
    return response()->json([]);
    }
}

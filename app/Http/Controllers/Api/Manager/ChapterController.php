<?php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ChapterController extends Controller
{
    /**
      * マネージャ配下のチャプター削除API
      *
      */
      public function delete()
      {
          return response()->json([]);
      }
}
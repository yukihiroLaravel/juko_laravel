<?php

namespace App\Http\Controllers\Api\Manager\Instructor;

use RuntimeException;
use App\Model\Instructor;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Manager\InstructorShowRequest;
use App\Http\Requests\Manager\InstructorIndexRequest;
use App\Http\Requests\Manager\InstructorPatchRequest;
use App\Http\Resources\Manager\InstructorShowResource;
use App\Http\Resources\Manager\InstructorIndexResource;

class InstructorController extends Controller
{
    /**
     * 講師情報取得API
     *
     * @param InstructorShowRequest $request
     * @return InstructorShowResource|\Illuminate\Http\JsonResponse
     */
    public function show(InstructorShowRequest $request)
    {
        $managerId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->findOrFail($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        //指定した講師IDが自分と配下の講師IDと一致しない場合は許可しない
        if (!in_array((int)$request->instructor_id, $instructorIds, true)) {
            return response()->json([
                'result'  => false,
                'message' => "Forbidden, not allowed to this instructor.",
            ], 403);
        }

        /** @var Instructor $instructor */
        $instructor = Instructor::findOrFail($request->instructor_id);

        return new InstructorShowResource($instructor);
    }

    /**
     * 講師一覧取得API
     *
     * @param InstructorIndexRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(InstructorIndexRequest $request)
    {
        // デフォルト値を設定
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sort_by', 'email');
        $order = $request->input('order', 'desc');

        $managerId = Auth::guard('instructor')->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->findOrFail($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // 講師情報を取得
        $instructors = Instructor::whereIn('id', $instructorIds)
            ->orderBy($sortBy, $order)
            ->paginate($perPage, ['*'], 'page', $page);

        return new InstructorIndexResource($instructors);
    }

    /**
     * 講師更新API
     *
     * @param InstructorPatchRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(InstructorPatchRequest $request)
    {
        // マネージャーと配下の講師情報を取得
        $managerId = $request->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->findOrFail($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        try {
            /** @var Instructor $instructor */
            $instructor = Instructor::FindOrFail($request->instructor_id);

            //指定した講師IDが自分と配下の講師IDと一致しない場合は許可しない
            if (!in_array($instructor->id, $instructorIds, true)) {
                return response()->json([
                    'result'  => false,
                    'message' => "Forbidden, not allowed to this instructor.",
                ], 403);
            }

            // 更新前の画像情報を取得
            $imagePath = $instructor->profile_image;
            $file = $request->file('profile_image');

            if (isset($file)) {
                // 更新前の画像ファイルを削除
                if (Storage::disk('public')->exists($instructor->profile_image)) {
                    Storage::disk('public')->delete($instructor->profile_image);
                }

                // 画像ファイルを保存
                $extension = $file->getClientOriginalExtension();
                $filename = Str::uuid()->toString() . '.' . $extension;
                $imagePath = Storage::disk('public')->putFileAs('instructor', $file, $filename);
            }

            $instructor->update([
                'nick_name' => $request->nick_name,
                'last_name' => $request->last_name,
                'first_name' => $request->first_name,
                'email' => $request->email,
                'profile_image' => $imagePath,
            ]);
            return response()->json([
                'result' => true,
            ]);
        } catch (RuntimeException $e) {
            Log::error($e);
            return response()->json([
                "result" => false,
            ], 500);
        }
    }
}

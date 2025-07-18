<?php

namespace App\Http\Controllers\Api\Manager\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\Instructor\IndexRequest;
use App\Http\Requests\Manager\Instructor\ShowRequest;
use App\Http\Requests\Manager\Instructor\StoreRequest;
use App\Http\Requests\Manager\Instructor\UpdateRequest;
use App\Http\Resources\Manager\InstructorIndexResource;
use App\Http\Resources\Manager\InstructorShowResource;
use App\Model\Instructor;
use App\Services\Auth\CredentialGeneratorService;
use App\Services\Instructor\StoreService;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * @tags Manager-Instructor
 */
class InstructorController extends Controller
{
    /**
     * 講師情報取得API
     *
     * @return InstructorShowResource|\Illuminate\Http\JsonResponse
     */
    public function show(ShowRequest $request)
    {
        $managerId = Auth::guard('instructor')->user()->id;

        // 配下の講師情報を取得
        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->findOrFail($managerId);
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        //指定した講師IDが自分と配下の講師IDと一致しない場合は許可しない
        if (! in_array((int) $request->instructor_id, $instructorIds, true)) {
            throw new AuthorizationException('Forbidden, not allowed to this instructor.');
        }

        /** @var Instructor $instructor */
        $instructor = Instructor::findOrFail($request->instructor_id);

        return new InstructorShowResource($instructor);
    }

    /**
     * 講師一覧取得API
     *
     * @return InstructorIndexResource
     */
    public function index(IndexRequest $request)
    {
        // デフォルト値を設定
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sort_by', 'email');
        $order = $request->input('order', 'desc');

        $managerId = Auth::guard('instructor')->user()->id;

        /** @var Instructor $manager */
        $manager = Instructor::with('managings')->findOrFail($managerId);

        // 管理する講師のIDを取得
        $instructorIds = $manager->managings->pluck('id')->toArray();

        // 講師情報を取得
        $instructors = Instructor::whereIn('id', $instructorIds)
            ->withCount([
                'courses as student_count' => function ($query) {
                    $query->join('attendances', 'courses.id', '=', 'attendances.course_id')
                        ->select(DB::raw('COUNT(DISTINCT attendances.student_id)'));
                },
            ])
            ->orderBy($sortBy, $order)
            ->paginate($perPage, ['*'], 'page', $page);

        return new InstructorIndexResource($instructors);
    }

    /**
     * 講師更新API
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateRequest $request)
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

            // 指定した講師IDが自分と配下の講師IDと一致しない場合は許可しない
            if (! in_array($instructor->id, $instructorIds, true)) {
                throw new AuthorizationException('Forbidden, not allowed to this instructor.');
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
                'result' => false,
            ], 500);
        }
    }

    /**
     * 講師新規仮登録API
     */
    public function store(
        StoreRequest $request,
        StoreService $registerService,
        CredentialGeneratorService $credentialGeneratorService
    ): JsonResponse {
        try {
            $registerService(
                email: $request->email,
                nickName: $request->nick_name,
                lastName: $request->last_name,
                firstName: $request->first_name,
                credentialGeneratorService: $credentialGeneratorService,
                managerId: Auth::guard('instructor')->user()->id
            );

            return response()->json(['result' => true]);
        } catch (Exception $e) {
            return response()->json(['result' => false, 'message' => $e->getMessage()], 400);
        }
    }
}

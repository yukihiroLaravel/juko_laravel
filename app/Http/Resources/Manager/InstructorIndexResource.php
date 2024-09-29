<?php

namespace App\Http\Resources\Manager;

use App\Model\Instructor;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

class InstructorIndexResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        /** @var LengthAwarePaginator $data */
        $data = $this->resource;

        return [
            'instructors' => $data->getCollection()->map(function (Instructor $instructor) {
                return [
                    'instructor_id' => $instructor->id,
                    'nick_name' => $instructor->nick_name,
                    'email' => $instructor->email,
                    'profile_image' => $instructor->profile_image,
                    'created_at' => $instructor->created_at,
                    'course_count' => $instructor->courses()->count(), 
                ];
            }),
            'pagination' => [
                'page' => $data->currentPage(),
                'total' => $data->total(),
            ],
        ];
    }
    private function mapCourses(Collection $courses)
    {
    return $courses->map(function (Course $course) {
        return [
            "course_id" => $course->id,
            "title" => $course->title,
            "status" => $course->status,
            "created_at" => $course->created_at,  // 作成日を追加
            "updated_at" => $course->updated_at,  // 更新日を追加
        ];
    })
    ->toArray();
    }

}

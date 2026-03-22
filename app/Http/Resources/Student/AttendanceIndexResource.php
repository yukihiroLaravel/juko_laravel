<?php

namespace App\Http\Resources\Student;

use App\Dto\Student\Attendance\ContinueFromDto;
use App\Http\Resources\Base\Student\AttendanceResource;
use App\Http\Resources\Base\Student\CourseResource;
use App\Http\Resources\Base\Student\InstructorResource;
use App\Http\Resources\Base\Student\TagResource;
use App\Model\Attendance;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceIndexResource extends JsonResource
{
    /** @var Attendance */
    public $resource;

    /** @var array<int, ContinueFromDto|null> */
    private static array $continueFromMap = [];

    /**
     * @param  array<int, ContinueFromDto|null>  $map
     */
    public static function withContinueFromMap(array $map): void
    {
        self::$continueFromMap = $map;
    }

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array $array
     */
    #[\Override]
    public function toArray($request): array
    {
        $continueFrom = self::$continueFromMap[$this->resource->id] ?? null;

        return [
            ...(new AttendanceResource($this->resource))->toArray($request),
            'course' => [
                ...(new CourseResource($this->resource->course))->toArray($request),
                'continue_from' => $continueFrom?->toArray(),
                'tags' => TagResource::collection($this->resource->course->tags),
                'instructor' => new InstructorResource($this->resource->course->instructor),
            ],
        ];
    }
}

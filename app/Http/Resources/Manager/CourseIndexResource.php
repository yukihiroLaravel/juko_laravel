<?php

namespace App\Http\Resources\Manager;

use App\Enums\Course\DeadlineTypeEnum;
use App\Http\Resources\Base\Instructor\CourseResource;
use App\Http\Resources\Base\Instructor\TagResource;
use App\Http\Resources\Base\Student\InstructorResource;
use App\Model\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseIndexResource extends JsonResource
{
    /** @var Course */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        $course = $this->resource;

        return [
            ...(new CourseResource($this->resource))->toArray($request),
            'instructor' => new InstructorResource($this->resource->instructor),
            'has_active_students' => $this->resource->attendances()->exists(),
            'tags' => TagResource::collection($this->resource['tags']),
            'deadline' => $this->buildDeadlinePayload($course),
        ];
    }

    /**
     * 期限情報（none / fixed_date / relative_days）を統一フォーマットで生成
     *
     * @return array{deadline_type:string,fixed_date:?string,relative_days:?int}
     */
    private function buildDeadlinePayload(Course $course): array
    {
        $enum = DeadlineTypeEnum::tryFrom($course->deadline_type) ?? DeadlineTypeEnum::NONE;
        $rel  = $course->courseDeadline;

        return match ($enum) {
            DeadlineTypeEnum::FIXED_DATE => [
                'deadline_type' => $enum->value,
                'fixed_date'    => $rel?->fixed_date ? (string) $rel->fixed_date : null,
                'relative_days' => null,
            ],
            DeadlineTypeEnum::RELATIVE_DAYS => [
                'deadline_type' => $enum->value,
                'fixed_date'    => null,
                'relative_days' => is_null($rel?->relative_days) ? null : (int) $rel->relative_days,
            ],
            default => [
                'deadline_type' => DeadlineTypeEnum::NONE->value,
                'fixed_date'    => null,
                'relative_days' => null,
            ],
        };
    }
}

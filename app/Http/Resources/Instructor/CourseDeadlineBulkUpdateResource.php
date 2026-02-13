<?php

namespace App\Http\Resources\Instructor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseDeadlineBulkUpdateResource extends JsonResource
{
    /**
     * @var array{updated_count: int}
     */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'result' => true,
            'updated_count' => $this->resource['updated_count'],
        ];
    }
}

<?php

namespace App\Http\Resources\Base\Instructor;

use App\Model\Instructor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstructorResource extends JsonResource
{
    /** @var Instructor */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function toArray(Request $request): array
    {
        return [
            'instructor_id' => $this->resource->id,
            'nick_name' => $this->resource->nick_name,
            'last_name' => $this->resource->last_name,
            'first_name' => $this->resource->first_name,
            'email' => $this->resource->email,
            'profile_image' => $this->resource->profile_image,
            'instructor_type' => $this->resource->type,
        ];
    }
}

<?php

namespace App\Http\Resources\Manager;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentStoreResource extends JsonResource
{
    /** @var \App\Model\Student */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->resource->id,
            'given_name_by_instructor' => $this->resource->given_name_by_instructor,
            'email' => $this->resource->email
        ];
    }
}

<?php

namespace App\Http\Resources\Shared;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResultResource extends JsonResource
{
    /** @var array<string, mixed> */
    public $resource;

    #[\Override]
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}

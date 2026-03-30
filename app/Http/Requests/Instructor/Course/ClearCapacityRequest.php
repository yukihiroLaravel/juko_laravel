<?php

namespace App\Http\Requests\Instructor\Course;

use Illuminate\Foundation\Http\FormRequest;

class ClearCapacityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'all' => ['boolean'],
            'courses' => ['required_without:all', 'array', 'min:1'],
            'courses.*' => ['integer', 'exists:courses,id,deleted_at,NULL'],
        ];
    }
}

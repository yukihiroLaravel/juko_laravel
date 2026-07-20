<?php

namespace App\Http\Requests\Instructor\Course;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'courses' => ['required', 'array', 'min:1'],
            'courses.*' => ['required', 'integer', 'exists:courses,id,deleted_at,NULL'],
        ];
    }
}

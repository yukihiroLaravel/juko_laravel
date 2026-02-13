<?php

namespace App\Http\Requests\Instructor;

use App\Enums\Course\DeadlineTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class BulkUpdateCourseDeadlineRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'courses' => ['required', 'array', 'min:1'],
            'courses.*' => [
                'integer',
                'exists:courses,id,deleted_at,NULL',
            ],

            'deadline_type' => ['required', new Enum(DeadlineTypeEnum::class)],
            'fixed_date' => ['nullable', 'date', 'required_if:deadline_type,fixed_date'],
            'relative_days' => ['nullable', 'integer', 'min:1', 'required_if:deadline_type,relative_days'],
        ];
    }
}

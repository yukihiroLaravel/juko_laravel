<?php

namespace App\Http\Requests\Manager\Course;

use Illuminate\Foundation\Http\FormRequest;

class ClearSelectedDeadlineRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // middlewareで権限制御しているならtrueでOK
        return true;
    }

    public function rules(): array
    {
        return [
            'courses' => ['required', 'array'],
            'courses.*' => ['integer', 'exists:courses,id,deleted_at,NULL'],
        ];
    }
}

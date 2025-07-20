<?php

namespace App\Http\Requests\Instructor\Chapter;

use Illuminate\Foundation\Http\FormRequest;

class DeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 認可は Controller や Policy 側で行う
    }

    public function rules(): array
    {
        return [
            'course_id' => 'required|integer|exists:courses,id',
        ];
    }
}
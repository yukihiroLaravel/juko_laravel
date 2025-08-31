<?php

namespace App\Http\Requests\Instructor\Course;

use App\Enums\Course\DeadlineTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => ['required'],
            'image' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'tag_id' => ['required', 'exists:tags,id'],
            'attendance_deadline' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'deadline_type' => ['required', new Enum(DeadlineTypeEnum::class)],
            'fixed_date' => ['required_if:deadline_type,fixed_date', 'date_format:Y-m-d', 'after_or_equal:today', 'nullable'],
            'relative_days' => ['required_if:deadline_type,relative_days', 'integer', 'min:1', 'nullable'],
        ];
    }
}

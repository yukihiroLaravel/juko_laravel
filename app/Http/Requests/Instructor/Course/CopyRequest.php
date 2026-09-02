<?php

namespace App\Http\Requests\Instructor\Course;

use App\Enums\Course\DeadlineTypeEnum;
use App\Rules\CourseStatusRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CopyRequest extends FormRequest
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

    #[\Override]
    protected function prepareForValidation()
    {
        $this->merge([
            'course_id' => $this->route('course_id'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            /** @ignoreParam */
            'course_id' => ['required', 'integer', 'exists:courses,id,deleted_at,NULL'],
            // 'title' => ['required', 'string'],
            // 'image' => ['mimes:jpg,png'],
            // 'status' => ['required', 'string', new CourseStatusRule],
            // 'deadline_type' => ['required', new Enum(DeadlineTypeEnum::class)],
            // 'fixed_date' => ['required_if:deadline_type,fixed_date', 'date_format:Y-m-d', 'after_or_equal:today', 'nullable'],
            // 'relative_days' => ['required_if:deadline_type,relative_days', 'integer', 'min:1', 'nullable'],
        ];
    }


}

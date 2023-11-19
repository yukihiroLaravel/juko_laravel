<?php

namespace App\Http\Requests\Instructor;

use App\Rules\AttendancePeriodRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRateRequest extends FormRequest
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
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'period' => ['required', 'string', new AttendancePeriodRule],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'course_id' => $this->route('course_id'),
            'period' => $this->route('period'),
        ]);
    }
}

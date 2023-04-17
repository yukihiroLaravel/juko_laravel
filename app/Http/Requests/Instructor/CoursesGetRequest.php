<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class CoursesGetRequest extends FormRequest
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
            'instructor_id' => ['required', 'integer'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'instructor_id' => $this->route('instructor_id'),
        ]);
    }

}

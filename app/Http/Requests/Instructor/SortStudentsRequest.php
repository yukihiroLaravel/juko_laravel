<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\SortStudentsColumnRule;
use App\Rules\SortStudentsOrderRule;

class SortStudentsRequest extends FormRequest
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

    protected function prepareForValidation()
    {
        $this->merge([
            'course_id' => $this->route('course_id'),
            'column' => $this->route('column'),
            'order' => $this->route('order'),
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
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'per_page' => ['integer', 'min:1'],
            'page' => ['integer', 'min:1'],
            'column' => ['required', 'string', new SortStudentsColumnRule],
            'order' => ['required', 'string', new SortStudentsOrderRule],
        ];
    }
}

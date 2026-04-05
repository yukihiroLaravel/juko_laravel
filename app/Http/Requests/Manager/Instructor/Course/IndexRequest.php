<?php

namespace App\Http\Requests\Manager\Instructor\Course;

use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
{
    #[\Override]
    protected function prepareForValidation()
    {
        $this->merge([
            'instructor_id' => $this->route('instructor_id'),
        ]);
    }

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
            /** @ignoreParam */
            'instructor_id' => ['required', 'integer', 'exists:instructors,id,deleted_at,NULL'],
            'per_page' => ['integer', 'min:1'],
            'page' => ['integer', 'min:1'],
        ];
    }
}

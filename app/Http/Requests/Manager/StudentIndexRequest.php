<?php

namespace App\Http\Requests\Manager;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\IndexSortByRule;

class StudentIndexRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        $this->merge([
            'course_id' => $this->route('course_id'),
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
           'course_id' => ['required', 'integer', 'exists:courses,id'],
           'per_page' => ['integer', 'min:1'],
           'page' => ['integer', 'min:1'],
           'sortBy' => ['string', new IndexSortByRule()],
           'order' => ['string', 'in:asc,desc'],
        ];
    }
}

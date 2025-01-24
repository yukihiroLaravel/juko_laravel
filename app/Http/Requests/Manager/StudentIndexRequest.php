<?php

namespace App\Http\Requests\Manager;

use App\Rules\IndexSortByRule;
use Illuminate\Foundation\Http\FormRequest;

class StudentIndexRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        $this->merge([
            'course_id' => $this->query('course_id'),
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
            'course_id' => ['nullable', 'integer', 'exists:courses,id,deleted_at,NULL'],
            'per_page' => ['integer', 'min:1'],
            'page' => ['integer', 'min:1'],
            'sort_by' => ['string', new IndexSortByRule],
            'order' => ['string', 'in:asc,desc'],
            'input_text' => ['string'],
            'start_date' => ['date_format:Y-m-d H:i:s'],
            'end_date' => ['date_format:Y-m-d H:i:s'],
        ];
    }
}

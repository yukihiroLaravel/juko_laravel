<?php

namespace App\Http\Requests\Manager;

use App\Rules\InstructorIndexSortByRule;
use Illuminate\Foundation\Http\FormRequest;

class InstructorIndexRequest extends FormRequest
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
            'per_page' => ['integer', 'min:1', 'max:100'],
            'page' => ['integer', 'min:1'],
            'sort_by' => ['string', new InstructorIndexSortByRule()],
            'order' => ['string', 'in:asc,desc'],
        ];
    }
}

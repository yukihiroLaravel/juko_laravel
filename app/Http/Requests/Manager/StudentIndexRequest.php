<?php

namespace App\Http\Requests\Manager;

use App\Rules\IndexSortByRule;
use Illuminate\Foundation\Http\FormRequest;

class StudentIndexRequest extends FormRequest
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
            'per_page' => ['integer', 'min:1'],
            'page' => ['integer', 'min:1'],
            'sort_by' => ['string', new IndexSortByRule],
            'order' => ['string', 'in:asc,desc'],
            'input_text' => ['string'],
            'start_date' => ['date_format:Y-m-d H:i:s'],
            'end_date' => ['date_format:Y-m-d H:i:s'],
            'courses' => ['array'], // 配列であることを指定
            'courses.*' => ['integer', 'distinct', 'exists:courses,id,deleted_at,NULL'], // 配列の各要素が整数で重複しないこと
        ];
    }
}

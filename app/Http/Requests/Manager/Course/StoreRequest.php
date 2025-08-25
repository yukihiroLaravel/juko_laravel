<?php

namespace App\Http\Requests\Manager\Course;

use Illuminate\Foundation\Http\FormRequest;

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
            'deadline_type' => ['required', 'in:none,fixed_date,relative_days'],
            'fixed_date' => ['required_if:deadline_type,fixed_date', 'date_format:Y-m-d', 'after_or_equal:today', 'nullable'],
            'relative_days' => ['required_if:deadline_type,relative_days', 'integer', 'min:1', 'nullable'],
        ];
    }
}

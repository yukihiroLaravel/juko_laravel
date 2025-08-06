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
            'attendance_deadline' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }
}

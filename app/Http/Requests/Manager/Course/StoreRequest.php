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
            'title' => ['required', 'string', 'max:30'],
            'image' => ['required', 'mimes:jpg,png', 'max:2048'],
            'attendance_deadline' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
}

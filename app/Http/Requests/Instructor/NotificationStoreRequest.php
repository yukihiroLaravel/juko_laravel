<?php

namespace App\Http\Requests\Instructor;

use Illuminate\Foundation\Http\FormRequest;

class NotificationStoreRequest extends FormRequest
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
            'course_id'     => ['required', 'exists:courses,id', 'integer'],
            'instructor_id' => ['exists:instructors,id', 'integer'],
            'title'         => ['required', 'string', 'max:50'],
            'type'          => ['required', 'in:always,once'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after:start_date'],
            'content'       => ['required', 'string', 'max:500'],
        ];
    }
}

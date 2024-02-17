<?php

namespace App\Http\Requests\Instructor;

use App\Rules\NotificationStoreStatusRule;
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

    protected function prepareForValidation()
    {
        $this->merge([
            'course_id' => $this->route('course_id'),
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
            'course_id'     => ['required', 'exists:courses,id', 'integer'],
            'title'         => ['required', 'string', 'max:50'],
            'type'          => ['required', new NotificationStoreStatusRule()],
            'start_date'    => ['required', 'date_format:Y-m-d H:i:s'],
            'end_date'      => ['required', 'date_format:Y-m-d H:i:s', 'after:start_date'],
            'content'       => ['required', 'string', 'max:500'],
        ];
    }
}

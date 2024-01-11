<?php

namespace App\Http\Requests\Instructor;

use App\Rules\InstructorUniqueEmailRule;
use App\Rules\InstructorTypeRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class InstructorPatchRequest extends FormRequest
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
            'instructor_id' => Auth::id(),
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
            'nick_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'first_name' => ['required', 'string'],
            'email' => ['required', 'email', new InstructorUniqueEmailRule(Auth::user()->email)],
            'instructor_id' => ['required', 'integer', 'exists:instructors,id,deleted_at,NULL'],
            'profile_image' => ['mimes:jpg,png', 'max:2048'],
        ];
    }
}

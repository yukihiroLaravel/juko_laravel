<?php

namespace App\Http\Requests\Manager;

use App\Rules\InstructorUniqueEmailRule;
use App\Rules\InstructorTypeRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'instructor_id' => $this->route('instructor_id'),
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
            'nick_name' => ['required', 'string','max:50'],
            'last_name' => ['required', 'string','max:50'],
            'first_name' => ['required', 'string','max:50'],
            'email' => ['required', 'email', new InstructorUniqueEmailRule($this->email),'max:255'],
            'instructor_id' => ['required', 'integer', 'exists:instructors,id,deleted_at,NULL'],
            'profile_image' => ['mimes:jpg,png', 'max:2048'],
            'type' =>  ['required', 'string', new InstructorTypeRule()],
        ];
    }
}

<?php

namespace App\Http\Requests\Manager\Instructor;

use App\Model\Instructor;
use App\Rules\InstructorUniqueEmailRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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

    #[\Override]
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
        $instructor = Instructor::find($this->instructor_id);

        return [
            'nick_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', new InstructorUniqueEmailRule($instructor?->email), 'max:255'],
            /** @ignoreParam */
            'instructor_id' => ['required', 'integer', 'exists:instructors,id,deleted_at,NULL'],
            'profile_image' => ['mimes:jpg,png', 'max:2048'],
        ];
    }
}

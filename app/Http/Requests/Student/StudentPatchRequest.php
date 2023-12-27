<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\UniqueEmailRule;
use App\Rules\SexRule;

class StudentPatchRequest extends FormRequest
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
        $user = $this->user();
        return [
            'nick_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'first_name' => ['required', 'string'],
            'email' => ['required', 'email', new UniqueEmailRule($user->email)],
            'occupation' => ['required', 'string'],
            'purpose' => ['required', 'string'],
            'birth_date' => ['required', 'date_format:Y-m-d'],
            'sex' => ['required', 'string', new SexRule()],
            'address' => ['required', 'string'],
            'profile_image' => ['sometimes','mimes:jpg,png', 'max:2048'],
        ];
    }
}

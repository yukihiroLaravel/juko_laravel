<?php

namespace App\Http\Requests\Instructor;

use App\Rules\InstructorUniqueEmailRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    // app/Http/Requests/Instructor/UpdateRequest.php

    public function rules(): array
    {
        return [
            'title'   => ['required', 'string', 'max:255'],
            'url'     => ['required', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            // status を必須にし、Enumの取りうる値（public/private）に制限
            'status'  => ['required', 'string', 'in:public,private'],
        ];
    }
}
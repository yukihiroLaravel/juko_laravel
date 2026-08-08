<?php

namespace App\Http\Requests\Manager\Course;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['integer', 'min:1'],
            'page' => ['integer', 'min:1'],
            'tag_id' => ['nullable', 'integer', 'exists:tags,id'],
            'search_word' => ['sometimes', 'string'],
        ];
    }
}

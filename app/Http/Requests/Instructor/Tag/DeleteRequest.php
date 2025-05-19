<?php

namespace App\Http\Requests\Instructor\Tag;

use Illuminate\Foundation\Http\FormRequest;

class DeleteRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tag_id' => ['required', 'integer', 'exists:tags,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'tag_id.required' => 'タグIDは必須です。',
            'tag_id.integer' => 'タグIDは整数である必要があります。',
            'tag_id.exists' => '指定されたタグが存在しません。',
        ];
    }
}

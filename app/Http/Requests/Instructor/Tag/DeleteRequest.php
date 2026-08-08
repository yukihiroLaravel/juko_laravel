<?php

namespace App\Http\Requests\Instructor\Tag;

use Illuminate\Contracts\Validation\ValidationRule;
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
     * バリデーション前にルートパラメータをマージ
     */
    #[\Override]
    protected function prepareForValidation()
    {
        $this->merge([
            'tag_id' => $this->route('tag_id'),
        ]);
    }

    /**
     * バリデーションルール
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            /** @ignoreParam */
            'tag_id' => ['required', 'integer', 'exists:tags,id'],
        ];
    }

    /**
     * バリデーションエラーメッセージ
     */
    #[\Override]
    public function messages(): array
    {
        return [
            'tag_id.required' => 'タグIDは必須です。',
            'tag_id.integer' => 'タグIDは整数である必要があります。',
            'tag_id.exists' => '指定されたタグが存在しません。',
        ];
    }
}

<?php

namespace App\Http\Requests\Manager\Chapter;

use Illuminate\Foundation\Http\FormRequest;

class DeleteRequest extends FormRequest
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
            /** @ignoreParam */
            'chapter_id' => ['required', 'integer', 'exists:chapters,id,deleted_at,NULL'],
        ];
    }

    #[\Override]
    protected function prepareForValidation()
    {
        $this->merge([
            'chapter_id' => $this->route('chapter_id'),
        ]);
    }
}

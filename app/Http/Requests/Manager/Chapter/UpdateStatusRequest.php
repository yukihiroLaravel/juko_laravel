<?php

namespace App\Http\Requests\Manager\Chapter;

use App\Rules\ChapterStatusRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusRequest extends FormRequest
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
            'chapter_id' => $this->route('chapter_id'),
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
            'chapters' => ['required', 'array'],
            'chapters.*' => ['required', 'integer', 'exists:chapters,id,deleted_at,NULL'],
            'status' => ['required', 'string', new ChapterStatusRule],
        ];
    }
}

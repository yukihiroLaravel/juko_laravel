<?php

namespace App\Http\Requests\Manager;

use App\Rules\NotificationUpdateStatusRule;
use App\Model\Notification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class NotificationUpdateRequest extends FormRequest
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
            'notification_id' => $this->route('notification_id'),
        ]);
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        //対象のデータがない（論理削除を含む）場合はエラーを返す
        try {
            // Log::info('Notification ID: ' . $this->route('notification_id'));
            $notification = Notification::findOrFail($this->route('notification_id'));

            //対象のデータがある→論理削除されているかチェック
            if ($notification->trashed()) {
                if ($notification->deleted_at === null) {
                    // 論理削除されている→エラーで返す
                    return response()->json([
                        'result' => false,
                        'message' => 'Notification is soft deleted.'
                    ], 403);
                }
            }

            return [
                'notification_id' => ['required', 'integer'],
                'type'            => ['required', new NotificationUpdateStatusRule()],
                'start_date'      => ['required', 'date_format:Y-m-d H:i:s'],
                'end_date'        => ['required', 'date_format:Y-m-d H:i:s', 'after:start_date'],
                'title'           => ['required', 'string', 'max:50'],
                'content'         => ['required', 'string', 'max:500'],
            ];

        //対象のデータがない→エラーで返す
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'result' => false,
                // 'message' => "This Notification does not exist."
            ], 500);
        }
    }
}

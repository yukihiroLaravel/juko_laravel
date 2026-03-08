<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\StudentLoginHistoryFactory;

class StudentLoginHistory extends Model
{
    use HasFactory;
    // 追加
    protected static function newFactory(): StudentLoginHistoryFactory
    {
        return StudentLoginHistoryFactory::new();
    }
    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'student_login_histories';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'logged_in_at',
    ];

    /**
     * 受講生を取得
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return array{
     *  logged_in_at: 'immutable_datetime',
     *  created_at: 'immutable_datetime',
     *  updated_at: 'immutable_datetime'
     * }
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'logged_in_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }
}

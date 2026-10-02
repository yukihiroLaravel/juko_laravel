<?php

namespace App\Model;

use App\Enums\Instructor\TypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property int|null $capacity_sum
 */
class Instructor extends Authenticatable
{
    use HasFactory;

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = 'instructors';

    // ソート対象フィールドの定数
    const SORT_BY_EMAIL = 'email';

    const SORT_BY_NICK_NAME = 'nick_name';

    const SORT_BY_CREATED_AT = 'created_at';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nick_name',
        'last_name',
        'first_name',
        'email',
        'password',
        'profile_image',
        'type',
    ];

    /**
     * シリアライズから除外する属性
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    public function isManager(): bool
    {
        return $this->type === TypeEnum::MANAGER;
    }

    /**
     * Get the remember token value.
     */
    #[\Override]
    public function getRememberToken(): ?string
    {
        return null;
    }

    /**
     * Set the remember token value.
     *
     * @param  string  $value
     */
    #[\Override]
    public function setRememberToken($value): void
    {
        // Do nothing.
    }

    /**
     * Get the name of the remember token.
     */
    #[\Override]
    public function getRememberTokenName(): string
    {
        return '';
    }

    /**
     * 講座を取得
     *
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /**
     * 配下の講師を取得
     *
     * @return BelongsToMany<Instructor, $this>
     */
    public function managings(): BelongsToMany
    {
        return $this->belongsToMany(Instructor::class, 'manage_instructors', 'manager_id', 'instructor_id');
    }

    /**
     * タグを取得
     *
     * @return HasMany<Tag, $this>
     */
    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class);
    }

    /**
     * @return array{
     *  created_at: 'immutable_datetime',
     *  updated_at: 'immutable_datetime',
     *  type: 'App\Enums\Instructor\TypeEnum'
     * }
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
            'type' => TypeEnum::class,
        ];
    }
}

<?php

namespace App\Services\Notification;

use App\Model\Notification;
use App\Model\Attendance;
use App\Model\CourseDeadline;
use App\Model\Student;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Carbon\CarbonImmutable;

class ShowService
{
    /**
     * お知らせ詳細の取得 + 受講期限チェック
     * 優先順位: 個別期限(attendances.attendance_deadline) > 講座設定(fixed/relative/none)
     *
     * 403時メッセージ: "The course has expired."
     */
    public function __invoke(Student $student, int $notificationId): Notification
    {
        // 通知本体（タイプで絞らない / 必要最小の関連のみ）
        /** @var Notification $notification */
        $notification = Notification::with(['course'])
            ->public()
            ->findOrFail($notificationId);

        // 受講していない通知は閲覧不可（従来の挙動を維持）
        $courseIds = Attendance::where('student_id', $student->id)
            ->pluck('course_id')->toArray();

        if (! in_array($notification->course_id, $courseIds, true)) {
            throw new AuthorizationException('Forbidden, not allowed to this notification.');
        }

        // === 1) 個別期限（最優先） ===========================================
        $individual = Attendance::where('student_id', $student->id)
            ->where('course_id', $notification->course_id)
            ->value('attendance_deadline'); // NULLなら個別期限なし

        if ($individual !== null) {
            $ind = $this->toCarbonImmutable($individual);
            if (now()->gte($ind->endOfDay())) {
                throw new AuthorizationException('The course has expired.');
            }
            // 個別期限が未来なら講座設定を見ずに閲覧許可
            return $notification;
        }

        // === 2) 講座設定（fixed / relative / none） =========================
        $mode = strtolower((string)($notification->course->deadline_type ?? ''));
        $deadline = CourseDeadline::where('course_id', $notification->course_id)->first();
        $now = CarbonImmutable::now();

        // relative 計算用（この受講生の受講開始=最初のcreated_at）
        $startedAt = Attendance::where('student_id', $student->id)
            ->where('course_id', $notification->course_id)
            ->oldest('created_at')
            ->value('created_at');
        $startedAt = $startedAt ? CarbonImmutable::parse($startedAt) : null;

        if ($mode === 'fixed') {
            $date = $deadline?->fixed_date;
            if ($date) {
                $exp = $this->toCarbonImmutable($date)->endOfDay();
                if ($now->gte($exp)) {
                    throw new AuthorizationException('The course has expired.');
                }
            }
            // fixed_date 未設定なら期限なし扱い
            return $notification;
        }

        if ($mode === 'relative') {
            $days = $deadline?->relative_days;
            if ($days !== null && $startedAt) {
                $exp = $startedAt->addDays((int)$days)->endOfDay();
                if ($now->gte($exp)) {
                    throw new AuthorizationException('The course has expired.');
                }
            }
            // relative_days 未設定 or startedAtなし → 期限なし扱い
            return $notification;
        }

        // none: 基本は期限なし。ただし course_deadlines に値があればフォールバック採用
        if ($mode === 'none' || $mode === '') {
            if ($deadline) {
                if ($deadline->fixed_date) {
                    $exp = $this->toCarbonImmutable($deadline->fixed_date)->endOfDay();
                    if ($now->gte($exp)) {
                        throw new AuthorizationException('The course has expired.');
                    }
                    return $notification;
                }
                if ($deadline->relative_days !== null && $startedAt) {
                    $exp = $startedAt->addDays((int)$deadline->relative_days)->endOfDay();
                    if ($now->gte($exp)) {
                        throw new AuthorizationException('The course has expired.');
                    }
                    return $notification;
                }
            }
            // 完全に未設定 → 期限なし
            return $notification;
        }

        // 不明モードは期限なし扱い（安全側に倒すなら 403 でも可）
        return $notification;
    }

    /** @param \DateTimeInterface|string $v */
    private function toCarbonImmutable($v): CarbonImmutable
    {
        if ($v instanceof \DateTimeInterface) {
            return CarbonImmutable::instance(Carbon::instance($v));
        }
        return CarbonImmutable::parse($v);
    }
}

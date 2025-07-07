<?php

namespace App\Policies;

use App\Model\Instructor;
use Illuminate\Support\Collection;

class NotificationPolicy
{
    public function update(Instructor $instructor, Collection $notifications, string $ownerColumn): bool
    {
        $manager = $instructor->load('managings');
        $instructorIds = $manager->managings->pluck('id')->toArray();
        $instructorIds[] = $manager->id;

        // 通知の所有者(instructor_id)が、ログイン中インストラクターの管轄内でなければfalse
        $notificationsInstructorIds = $notifications->pluck('instructor_id')->unique()->toArray();
        if (array_diff($notificationsInstructorIds, $instructorIds)) {
            throw new \Exception('Instructor not authorized for one or more notification instructor_ids.');
        }

        // 所有カラムが一致しない通知が含まれていればfalse
        $userId = $instructor->id; // または他に必要なID
        if ($notifications->contains(fn($n) => $n->{$ownerColumn} !== $userId)) {
            throw new \Exception('Owner column mismatch.');
        }

        return true;
    }
}

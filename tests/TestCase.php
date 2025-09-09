<?php

namespace Tests;

use App\Model\Instructor;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * マネージャーでログインする
     */
    protected function loginAsManager(int $managerId = 1): void
    {
        $manager = Instructor::find($managerId);
        $this->actingAs($manager, 'instructor');
    }

    /**
     * 講師でログインする
     */
    protected function loginAsInstructor(int $instructorId = 2): void
    {
        $instructor = Instructor::find($instructorId);
        $this->actingAs($instructor, 'instructor');
    }
}

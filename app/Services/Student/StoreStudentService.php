<?php

namespace App\Services\Student;

use App\Model\Student;

class StoreStudentService
{
    public function __invoke(array $data): void
    {
        Student::create([
            'given_name_by_instructor' => $data['given_name_by_instructor'],
            'email' => $data['email'],
        ]);
    }
}
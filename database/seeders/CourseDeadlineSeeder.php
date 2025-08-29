<?php

namespace Database\Seeders;

use App\Model\CourseDeadline;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CourseDeadlineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CourseDeadline::insert([
            [
                'course_id' => 2,
                'fixed_date' => CarbonImmutable::now()->addMonth(1),
                'relative_days' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}

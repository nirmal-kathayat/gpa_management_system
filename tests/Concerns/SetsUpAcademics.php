<?php

namespace Tests\Concerns;

use App\Models\AcademicYear;
use App\Models\School;
use App\Models\SchoolClass;

/**
 * The years and classes a test school needs before a student can be filed
 * or a card issued: 2080-2082 with 2081 current, and classes 9, 10, 11.
 */
trait SetsUpAcademics
{
    protected function setUpAcademics(School $school): void
    {
        if (! AcademicYear::exists()) {
            foreach (['2080', '2081', '2082'] as $year) {
                AcademicYear::create(['year' => $year, 'is_current' => $year === '2081']);
            }
        }

        foreach (['9' => ['A', 'B'], '10' => ['A', 'B'], '11' => ['A']] as $name => $sections) {
            SchoolClass::create(['school_id' => $school->id, 'name' => $name, 'sections' => $sections, 'sort_order' => (int) $name]);
        }
    }
}

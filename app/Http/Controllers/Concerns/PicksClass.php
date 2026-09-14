<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Student;
use Illuminate\Http\Request;

/**
 * For screens that start by naming a class: school, class, section and
 * academic year, read from the query string and checked against what the
 * user may see. Rendered by partials/class-picker.
 */
trait PicksClass
{
    /**
     * The school, class, section and year the page is filtered to. A school
     * id outside the user's reach simply does not select; when there is only
     * one school to choose from it is chosen.
     */
    protected function classFilters(Request $request, array $allowedSchools): array
    {
        $schoolId = (int) $request->input('school_id');

        if (! in_array($schoolId, $allowedSchools, true)) {
            $schoolId = count($allowedSchools) === 1 ? $allowedSchools[0] : null;
        }

        return [
            'school_id' => $schoolId,
            'class' => trim((string) $request->input('class')) ?: null,
            'section' => trim((string) $request->input('section')) ?: null,
            'academic_year' => preg_match('/^\d{4}$/', (string) $request->input('academic_year'))
                ? $request->input('academic_year') : null,
        ];
    }

    /**
     * Every class and section that has students, per school, for the cascading
     * pickers. Small enough to ship whole: a few hundred entries at the most.
     */
    protected function classMap(array $schoolIds): array
    {
        $map = [];

        $rows = Student::whereIn('school_id', $schoolIds)
            ->select('school_id', 'class', 'section')
            ->distinct()
            ->orderBy('class')
            ->orderBy('section')
            ->get();

        foreach ($rows as $row) {
            $map[$row->school_id][$row->class][] = $row->section;
        }

        return $map;
    }
}

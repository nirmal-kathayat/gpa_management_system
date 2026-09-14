<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
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
            // No year on the query string means the year in progress.
            'academic_year' => preg_match('/^\d{4}$/', (string) $request->input('academic_year'))
                ? $request->input('academic_year')
                : ($request->has('academic_year') ? null : AcademicYear::current()),
        ];
    }

    /**
     * Every class and section each school runs, from the Years & Classes
     * screen, for the cascading pickers.
     */
    protected function classMap(array $schoolIds): array
    {
        return SchoolClass::mapFor($schoolIds);
    }
}

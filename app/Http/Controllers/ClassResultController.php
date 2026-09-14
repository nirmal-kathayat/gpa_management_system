<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PicksClass;
use App\Models\GradeSystem;
use App\Models\School;
use App\Models\StudentReport;
use App\Support\ClassResultSheet;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * The class result sheet: one exam, every student and subject in a class on
 * one landscape page, for the notice board and the school's own record.
 */
class ClassResultController extends Controller
{
    use PicksClass;

    public function index(Request $request)
    {
        $schools = $this->selectableSchools();
        $filters = $this->filters($request, $schools->pluck('id')->all());

        return view('results.index', [
            'schools' => $schools,
            'classMap' => $this->classMap($schools->pluck('id')->all()),
            'exams' => MarksEntryController::EXAMS,
            'years' => StudentReport::distinct()->orderByDesc('academic_year')->pluck('academic_year'),
            'filters' => $filters,
            'sheet' => $filters['complete'] ? $this->sheet($filters) : null,
            'school' => $filters['school_id'] ? School::find($filters['school_id']) : null,
            'gradeSystem' => GradeSystem::active()->ordered()->get(),
        ]);
    }

    public function pdf(Request $request)
    {
        $filters = $this->filters($request, $this->selectableSchools()->pluck('id')->all());

        abort_unless($filters['complete'], 404);

        $sheet = $this->sheet($filters);

        $pdf = Pdf::loadView('results.pdf', [
            'sheet' => $sheet,
            'school' => School::find($filters['school_id']),
            'gradeSystem' => GradeSystem::active()->ordered()->get(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download(sprintf(
            'result-class-%s-%s-%s-%s.pdf',
            $filters['class'], $filters['section'], $filters['exam_type'], $filters['academic_year']
        ));
    }

    private function filters(Request $request, array $allowedSchools): array
    {
        $filters = $this->classFilters($request, $allowedSchools) + [
            'exam_type' => array_key_exists($request->input('exam_type', ''), MarksEntryController::EXAMS)
                ? $request->input('exam_type') : null,
        ];

        $filters['complete'] = ! in_array(null, $filters, true);

        return $filters;
    }

    private function sheet(array $filters): ClassResultSheet
    {
        $this->authorizeSchool($filters['school_id']);

        return new ClassResultSheet(
            array_diff_key($filters, ['complete' => true]),
            MarksEntryController::EXAMS[$filters['exam_type']]
        );
    }
}

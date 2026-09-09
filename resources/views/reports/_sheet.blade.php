{{--
    The mark sheet. Rendered on screen by reports/show and, with the same markup
    and the same stylesheet, into the PDF by reports/pdf - so what a school sees
    is what it prints.

    Laid out with tables rather than flex or grid because DomPDF understands
    tables and little else.
--}}
@php
    $school = $report->student->school;
    $student = $report->student;

    $examTypes = [
        'first_terminal' => '1st Terminal',
        'second_terminal' => '2nd Terminal',
        'final_terminal' => 'Final Terminal',
        'pre_board' => 'Pre-Board',
    ];

    $behaviours = [
        'Class Response' => $report->class_response,
        'Discipline' => $report->discipline,
        'Leadership' => $report->leadership,
        'Neatness' => $report->neatness,
        'Punctuality' => $report->punctuality,
        'Regularity' => $report->regularity,
        'Social Conduct' => $report->social_conduct,
        'Sports / Games' => $report->sports_game,
    ];

    // Grades the scale itself marks as failing, so a failed subject shows red.
    $failingLetters = $gradeSystem->where('is_failing', true)->pluck('letter_grade')->all();

    $failed = $report->result_status === 'FAILED';

    // Every sheet carries a crest: the uploaded logo, or the school's initials
    // in the same soft-blue mark students already get.
    $crest = \Illuminate\Support\Str::of($school?->name ?: 'School')
        ->explode(' ')
        ->take(2)
        ->map(fn ($word) => \Illuminate\Support\Str::substr($word, 0, 1))
        ->implode('');
    $crest = \Illuminate\Support\Str::upper($crest);

    $identity = [
        'Name' => $student->name,
        'Class' => $student->class,
        'Section' => $student->section ?: '-',
        'Roll No.' => $student->roll_number,
        'Symbol No.' => $student->symbol_number ?: '-',
    ];

    $summary = [
        'GPA Obtained' => number_format((float) $report->final_gpa, 2),
        'Final Grade' => $report->final_grade ?: '-',
        'Position' => $report->position ? 'Rank '.$report->position : 'N/A',
        'Attendance' => $report->total_days
            ? $report->attendance_days.' / '.$report->total_days.' days'
            : 'N/A',
    ];

    // A dash rather than an empty cell, so a missing mark is obviously missing.
    $mark = function ($value) {
        return $value === null || $value === ''
            ? '<span class="sheet-blank">&ndash;</span>'
            : e(rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.'));
    };
@endphp

<div class="sheet">
    <table class="sheet-brand">
        <tr>
            <td class="sheet-brand-side">
                @if($school?->logo)
                    <img class="sheet-crest" alt="{{ $school->name }}"
                         src="{{ $pdf ?? false ? public_path($school->logo) : asset($school->logo) }}">
                @else
                    {{-- A one-cell table, because that is the only vertical
                         centring DomPDF gets right. --}}
                    <table class="sheet-monogram"><tr><td>{{ $crest }}</td></tr></table>
                @endif
            </td>
            <td class="sheet-brand-main">
                <h1 class="sheet-school">{{ $school?->name ?? 'School' }}</h1>
                <p class="sheet-school-meta">
                    {{ $school?->address }}@if($school?->phone) &nbsp;·&nbsp; Phone: {{ $school->phone }}@endif
                </p>
            </td>
            <td class="sheet-brand-side"></td>
        </tr>
    </table>

    <div class="sheet-rule"></div>

    <div class="sheet-title">
        <span class="sheet-title-main">Mark Sheet</span>
        <span class="sheet-title-sub">Final Examination &nbsp;·&nbsp; {{ $report->academic_year }}</span>
    </div>

    <table class="sheet-identity">
        <tr>
            @foreach($identity as $label => $value)
                <td>
                    <span class="k">{{ $label }}</span>
                    <span class="v">{{ $value ?: '-' }}</span>
                </td>
            @endforeach
        </tr>
    </table>

    {{-- DomPDF lays tables out on its own, so every column states its own width;
         without them the subject name gets squeezed into three lines. --}}
    <table class="sheet-table">
        <thead>
            <tr>
                <th width="4%" rowspan="2">S.N.</th>
                <th width="25%" class="is-left" rowspan="2">Subject</th>
                <th width="5%" rowspan="2">F.M.</th>
                @foreach($examTypes as $label)
                    <th width="12%" colspan="2">{{ $label }}</th>
                @endforeach
                <th width="10%" rowspan="2">Grade</th>
                <th width="8%" rowspan="2">GP</th>
            </tr>
            <tr>
                @foreach($examTypes as $label)
                    <th width="6%">TH</th>
                    <th width="6%">PR</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($subjects as $index => $subject)
                @php
                    $final = $marks[$subject->id]['final_terminal'] ?? null;
                    $letter = $final?->first()?->letter_grade;
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="is-left sheet-subject">{{ $subject->name }}</td>
                    <td class="sheet-full">{{ $subject->full_marks }}</td>
                    @foreach($examTypes as $type => $label)
                        @php $row = ($marks[$subject->id][$type] ?? null)?->first(); @endphp
                        <td>{!! $mark($row?->theory_marks) !!}</td>
                        <td>{!! $mark($row?->practical_marks) !!}</td>
                    @endforeach
                    <td class="sheet-grade {{ $letter && in_array($letter, $failingLetters, true) ? 'is-fail' : '' }}">
                        {!! $letter ? e($letter) : '<span class="sheet-blank">&ndash;</span>' !!}
                    </td>
                    <td>{!! $mark($final?->first()?->grade_point) !!}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13">No marks were recorded for this report card.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="sheet-split">
        <tr>
            <td>
                <p class="sheet-block-title">Result Summary</p>
                <table class="sheet-pairs">
                    @foreach($summary as $label => $value)
                        <tr class="{{ $loop->first ? 'is-strong' : '' }}">
                            <td>{{ $label }}</td>
                            <td>{{ $value }}</td>
                        </tr>
                    @endforeach
                </table>
                <div class="sheet-result {{ $failed ? 'is-fail' : '' }}">
                    {{ $report->result_status ?: 'PASSED' }}
                </div>
            </td>
            <td>
                <p class="sheet-block-title">Behavioural Assessment</p>
                <table class="sheet-pairs is-double">
                    @foreach(collect($behaviours)->chunk(2) as $pair)
                        <tr>
                            @foreach($pair as $label => $grade)
                                <td>{{ $label }}</td>
                                <td>{{ $grade ?: '-' }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
    </table>

    {{-- The scale reads across the page: a legend, not a second data table. --}}
    <p class="sheet-block-title sheet-legend-title">Grading System</p>
    <table class="sheet-table sheet-legend">
        <tbody>
            <tr>
                <th class="is-left">Grade</th>
                @foreach($gradeSystem as $grade)
                    <td class="sheet-grade {{ $grade->is_failing ? 'is-fail' : '' }}">{{ $grade->letter_grade }}</td>
                @endforeach
            </tr>
            <tr>
                <th class="is-left">Grade Point</th>
                @foreach($gradeSystem as $grade)
                    <td>{{ rtrim(rtrim(number_format($grade->grade_point, 2), '0'), '.') }}</td>
                @endforeach
            </tr>
            <tr>
                <th class="is-left">Percentage</th>
                @foreach($gradeSystem as $grade)
                    <td>{{ $grade->marks_from }}&ndash;{{ $grade->marks_to }}</td>
                @endforeach
            </tr>
            <tr>
                <th class="is-left">Remarks</th>
                @foreach($gradeSystem as $grade)
                    <td>{{ $grade->description }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <p class="sheet-block-title sheet-legend-title">Result</p>
    <table class="sheet-table sheet-legend">
        <tbody>
            <tr>
                <th class="is-left">Division</th>
                @foreach(\App\Support\GradeCalculator::RESULT_BANDS as $band)
                    <td>{{ \Illuminate\Support\Str::headline(strtolower(
                        \Illuminate\Support\Str::after($band['status'], 'PASSED WITH ') ?: $band['status']
                    )) }}</td>
                @endforeach
                <td>Failed</td>
            </tr>
            <tr>
                <th class="is-left">Final GPA</th>
                @foreach(\App\Support\GradeCalculator::RESULT_BANDS as $band)
                    <td>{{ number_format($band['min'], 1) }} +</td>
                @endforeach
                <td>Any subject not passed</td>
            </tr>
        </tbody>
    </table>

    <div class="sheet-remarks">
        <span class="k">Class teacher's remarks:</span>
        {{ $report->remarks ?: 'No remarks were recorded.' }}
    </div>

    <table class="sheet-signs">
        <tr class="sheet-signs-spacer">
            <td></td><td></td><td></td><td></td>
        </tr>
        <tr>
            <td>
                <span class="v">{{ optional($report->issue_date)->format('d M Y') ?: '-' }}</span>
                Issue Date
            </td>
            <td><span class="v">&nbsp;</span>Class Teacher</td>
            <td><span class="v">&nbsp;</span>Exam Coordinator</td>
            <td><span class="v">&nbsp;</span>Principal</td>
        </tr>
    </table>
</div>

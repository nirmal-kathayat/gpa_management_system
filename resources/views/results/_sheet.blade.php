{{--
    The class result sheet. Rendered on screen by results/index and, with the
    same markup and stylesheet, as a landscape PDF by results/pdf ($pdf = true).

    Expects: $sheet (ClassResultSheet), $school, $gradeSystem.
--}}
@php
    $pdf = $pdf ?? false;
    $filters = $sheet->filters;
    $summary = $sheet->summary;

    $crest = \Illuminate\Support\Str::of($school?->name ?: 'School')
        ->explode(' ')
        ->take(2)
        ->map(fn ($word) => \Illuminate\Support\Str::substr($word, 0, 1))
        ->implode('');

    $num = fn ($value) => $value === null
        ? '–'
        : rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
@endphp

<div class="rsheet">
    <table class="rsheet-brand">
        <tr>
            <td class="rsheet-brand-side">
                @if($school?->logo)
                    <img class="rsheet-crest" alt="{{ $school->name }}"
                         src="{{ $pdf ? public_path($school->logo) : asset($school->logo) }}">
                @else
                    <table class="rsheet-monogram"><tr><td>{{ $crest }}</td></tr></table>
                @endif
            </td>
            <td class="rsheet-brand-main">
                <h1 class="rsheet-school">{{ $school?->name ?? 'School' }}</h1>
                <p class="rsheet-school-meta">
                    {{ $school?->address }}@if($school?->phone) &nbsp;·&nbsp; Phone: {{ $school->phone }}@endif
                </p>
                <p class="rsheet-heading">Class Result Sheet</p>
                <p class="rsheet-sub">
                    Class {{ $filters['class'] }} {{ $filters['section'] }}
                    &nbsp;·&nbsp; {{ $sheet->examLabel }}
                    &nbsp;·&nbsp; Academic Year {{ $filters['academic_year'] }}
                </p>
            </td>
            <td class="rsheet-brand-side rsheet-brand-facts">
                <span class="k">Students</span><span class="v">{{ $summary['students'] }}</span>
                <span class="k">Appeared</span><span class="v">{{ $summary['appeared'] }}</span>
                <span class="k">Pass rate</span><span class="v">{{ $summary['pass_rate'] === null ? '–' : $num($summary['pass_rate']).'%' }}</span>
            </td>
        </tr>
    </table>

    <table class="rsheet-table">
        <thead>
            <tr>
                <th rowspan="2" class="c-roll">Roll</th>
                <th rowspan="2" class="c-name">Student</th>
                @foreach($sheet->subjects as $subject)
                    <th colspan="2" class="c-subject">
                        {{ $subject->name }}
                        <span class="fm">FM {{ $subject->full_marks }} &nbsp;·&nbsp; PM {{ $subject->pass_marks }}</span>
                    </th>
                @endforeach
                <th rowspan="2" class="c-total">Total</th>
                <th rowspan="2" class="c-pct">%</th>
                <th rowspan="2" class="c-gpa">GPA</th>
                <th rowspan="2" class="c-grade">Grade</th>
                <th rowspan="2" class="c-result">Result</th>
                <th rowspan="2" class="c-rank">Rank</th>
            </tr>
            <tr>
                @foreach($sheet->subjects as $subject)
                    <th class="c-sub">Marks</th>
                    <th class="c-sub">GP</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($sheet->rows as $row)
                <tr class="{{ $row['absent'] ? 'is-absent' : ($row['passed'] ? '' : 'is-failed') }}">
                    <td class="c-roll">{{ $row['report']->roll_number }}</td>
                    <td class="c-name">{{ $row['report']->student?->name ?? '—' }}</td>
                    @foreach($row['cells'] as $cell)
                        @if($cell === null)
                            <td class="c-sub is-blank">AB</td>
                            <td class="c-sub is-blank">–</td>
                        @else
                            <td class="c-sub {{ $cell['fail'] ? 'is-fail' : '' }}">{{ $num($cell['marks']) }}</td>
                            <td class="c-sub gp {{ $cell['fail'] ? 'is-fail' : '' }}">{{ $num($cell['point']) }}</td>
                        @endif
                    @endforeach
                    @if($row['absent'])
                        <td class="c-total is-blank">–</td>
                        <td class="c-pct is-blank">–</td>
                        <td class="c-gpa is-blank">–</td>
                        <td class="c-grade is-blank">–</td>
                        <td class="c-result">Absent</td>
                        <td class="c-rank is-blank">–</td>
                    @else
                        <td class="c-total">{{ $num($row['obtained']) }}<span class="of">/{{ $num($row['full']) }}</span></td>
                        <td class="c-pct">{{ $num($row['percentage']) }}</td>
                        <td class="c-gpa">{{ number_format($row['gpa'], 2) }}</td>
                        <td class="c-grade">{{ $row['grade'] }}</td>
                        <td class="c-result">{{ $row['passed'] ? \Illuminate\Support\Str::of($row['status'])->after('PASSED WITH ')->title()->replace('Passed', 'Pass') : 'Fail' }}</td>
                        <td class="c-rank">{{ $row['rank'] }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="c-name">Passed / sat &nbsp;·&nbsp; class average</td>
                @foreach($summary['subjects'] as $stat)
                    <td class="c-sub">{{ $stat['passed'] }}/{{ $stat['sat'] }}</td>
                    <td class="c-sub gp">{{ $num($stat['average']) }}</td>
                @endforeach
                <td colspan="6" class="c-foot-note">
                    Passed {{ $summary['passed'] }} &nbsp;·&nbsp; Failed {{ $summary['failed'] }}
                    &nbsp;·&nbsp; Absent {{ $summary['absent'] }}
                    &nbsp;·&nbsp; Average GPA {{ $summary['average_gpa'] === null ? '–' : number_format($summary['average_gpa'], 2) }}
                </td>
            </tr>
        </tfoot>
    </table>

    <table class="rsheet-foot">
        <tr>
            <td class="rsheet-toppers">
                @if($summary['toppers']->isNotEmpty())
                    <span class="k">Top of the class</span>
                    @foreach($summary['toppers'] as $top)
                        <span class="topper">
                            <b>{{ $top['rank'] }}.</b> {{ $top['report']->student?->name }}
                            <em>GPA {{ number_format($top['gpa'], 2) }}</em>
                        </span>
                    @endforeach
                @endif
            </td>
            <td class="rsheet-legend">
                <span class="k">Grading</span>
                @foreach($gradeSystem as $band)
                    <span class="band {{ $band->is_failing ? 'is-fail' : '' }}">{{ $band->letter_grade }} {{ $band->marks_from }}–{{ $band->marks_to }}% = {{ $num($band->grade_point) }}</span>
                @endforeach
                <span class="band">AB = absent</span>
            </td>
        </tr>
    </table>

    <table class="rsheet-signs">
        <tr>
            <td><span class="v">{{ now()->format('d M Y') }}</span>Date</td>
            <td><span class="v">&nbsp;</span>Class Teacher</td>
            <td><span class="v">&nbsp;</span>Exam Coordinator</td>
            <td><span class="v">&nbsp;</span>Principal</td>
        </tr>
    </table>
</div>

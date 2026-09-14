<?php

namespace App\Console\Commands;

use App\Models\StudentReport;
use App\Support\ReportGrader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Re-runs the grader over every report card from the marks it already holds.
 *
 * A report is graded when it is saved, so a fix to the grading rules (or a
 * change to the scale) leaves the ones already issued with their old grades
 * until they are opened and saved again. This does that for all of them.
 */
class RegradeReports extends Command
{
    protected $signature = 'reports:regrade {--dry-run : Report what would change without saving}';

    protected $description = 'Regrade every report card from its stored marks';

    public function handle(ReportGrader $grader): int
    {
        $changed = 0;

        // One transaction for the whole run: a dry run is simply never committed.
        DB::beginTransaction();

        foreach (StudentReport::with('student')->cursor() as $report) {
            $before = $report->only(['final_gpa', 'final_grade', 'result_status']);
            $after = $grader->regrade($report);

            if (round((float) $before['final_gpa'], 2) !== round((float) $after['final_gpa'], 2)
                || $before['final_grade'] !== $after['final_grade']
                || $before['result_status'] !== $after['result_status']) {
                $changed++;
                $this->line(sprintf(
                    '#%d %s (%s): %s %s %s  ->  %s %s %s',
                    $report->id,
                    $report->student?->name ?? '?',
                    $report->academic_year,
                    $before['final_gpa'], $before['final_grade'], $before['result_status'],
                    $after['final_gpa'], $after['final_grade'], $after['result_status']
                ));
            }
        }

        if ($this->option('dry-run')) {
            DB::rollBack();
            $this->info($changed.' report card(s) would change. Nothing was saved.');

            return self::SUCCESS;
        }

        // Ranks are within a class and year, so one pass per group is enough.
        StudentReport::with('student')->get()
            ->unique(fn ($r) => implode('|', [
                $r->student?->school_id, $r->class, $r->section, $r->academic_year,
            ]))
            ->each(fn ($r) => $grader->recalculatePositions($r));

        DB::commit();
        $this->info($changed.' report card(s) changed.');

        return self::SUCCESS;
    }
}

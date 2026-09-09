<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A student has one report card per academic year. Duplicates were possible and
 * did happen - three reports existed for one student and year, all sharing the
 * same marks. The newest is kept; the rest go, taking their (now linked) marks
 * with them.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('student_reports')->orderByDesc('id')->get()->groupBy(
            fn ($report) => $report->student_id.'|'.$report->academic_year
        )->each(function ($reports) {
            $stale = $reports->skip(1)->pluck('id');

            if ($stale->isNotEmpty()) {
                DB::table('student_marks')->whereIn('student_report_id', $stale)->delete();
                DB::table('student_reports')->whereIn('id', $stale)->delete();
            }
        });

        Schema::table('student_reports', function (Blueprint $table) {
            $table->unique(['student_id', 'academic_year']);
        });
    }

    public function down(): void
    {
        Schema::table('student_reports', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'academic_year']);
        });
    }
};

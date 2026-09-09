<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ties a mark to the report card it belongs to.
 *
 * Marks were found by (student_id, academic_year), and nothing stopped a student
 * having two report cards for the same year - so several reports shared one set
 * of marks. Editing one rewrote the others, and deleting one emptied them.
 *
 * The column stays nullable because a mark could exist with no matching report;
 * the application always sets it, and marks go when their report does.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_marks', function (Blueprint $table) {
            $table->foreignId('student_report_id')->nullable()->after('student_id')
                ->constrained('student_reports')->cascadeOnDelete();
        });

        // Existing marks belong to the oldest report for that student and year.
        DB::table('student_reports')->orderBy('id')->get()->groupBy(
            fn ($report) => $report->student_id.'|'.$report->academic_year
        )->each(function ($reports) {
            $report = $reports->first();

            DB::table('student_marks')
                ->whereNull('student_report_id')
                ->where('student_id', $report->student_id)
                ->where('academic_year', $report->academic_year)
                ->update(['student_report_id' => $report->id]);
        });

        // Anything still unattached belongs to no report card at all.
        DB::table('student_marks')->whereNull('student_report_id')->delete();
    }

    public function down(): void
    {
        Schema::table('student_marks', function (Blueprint $table) {
            $table->dropForeign(['student_report_id']);
            $table->dropColumn('student_report_id');
        });
    }
};

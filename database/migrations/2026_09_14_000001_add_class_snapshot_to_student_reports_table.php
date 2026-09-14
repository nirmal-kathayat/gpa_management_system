<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A report card records the class the student was in when it was issued.
 *
 * It used to read the class off the student record, so a student promoted
 * from 9 to 10 had every earlier report card - and every earlier rank -
 * silently move to class 10 with them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_reports', function (Blueprint $table) {
            $table->string('class', 50)->nullable()->after('academic_year');
            $table->string('section', 10)->nullable()->after('class');
            $table->integer('roll_number')->nullable()->after('section');
        });

        // Existing reports take the class the student is in today - the best
        // information there is, and what they were already showing.
        foreach (['class', 'section', 'roll_number'] as $column) {
            DB::table('student_reports')->update([
                $column => DB::raw("(select {$column} from students where students.id = student_reports.student_id)"),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('student_reports', function (Blueprint $table) {
            $table->dropColumn(['class', 'section', 'roll_number']);
        });
    }
};

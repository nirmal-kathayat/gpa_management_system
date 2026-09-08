<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_reports', function (Blueprint $table) {
            if (!Schema::hasColumn('student_reports', 'result_status')) {
                $table->string('result_status')->nullable()->after('final_grade');
            }
            if (!Schema::hasColumn('student_reports', 'result_remarks')) {
                $table->text('result_remarks')->nullable()->after('result_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_reports', function (Blueprint $table) {
            $table->dropColumn(['result_status', 'result_remarks']);
        });
    }
};

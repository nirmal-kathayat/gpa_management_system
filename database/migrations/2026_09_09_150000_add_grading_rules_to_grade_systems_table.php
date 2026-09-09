<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Makes the grade scale the source of truth for grading.
 *
 * Until now ReportController decided a subject had been failed by matching the
 * literal strings 'NG' and 'F' and by treating any grade point under 2.0 as a
 * failure - which quietly overrode whatever scale an admin had set up. A band
 * now says for itself whether it is a pass, so renaming NG or moving the pass
 * mark is a data change rather than a code change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade_systems', function (Blueprint $table) {
            $table->renameColumn('remarks', 'description');
        });

        Schema::table('grade_systems', function (Blueprint $table) {
            $table->boolean('is_failing')->default(false)->after('description');
            $table->boolean('is_active')->default(true)->after('is_failing');
        });
    }

    public function down(): void
    {
        Schema::table('grade_systems', function (Blueprint $table) {
            $table->dropColumn(['is_failing', 'is_active']);
        });

        Schema::table('grade_systems', function (Blueprint $table) {
            $table->renameColumn('description', 'remarks');
        });
    }
};

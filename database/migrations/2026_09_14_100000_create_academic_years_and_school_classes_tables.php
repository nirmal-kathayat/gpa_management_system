<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The academic structure: which years exist and which is the current one,
 * and which classes and sections each school runs.
 *
 * Both used to be free text - a student's class was whatever was typed, so
 * "9", "Nine" and "Class 9" were three classes - and the pickers had to guess
 * the list from the students. Reports and students keep their string columns
 * (a card prints the name, and an old card keeps the name it was issued
 * under); these tables constrain what can be chosen and say what is current.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('year', 4)->unique();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('name', 50);
            // Sections as a list of names, e.g. ["A", "B"]. A class with no
            // sections still needs one for the students to sit in.
            $table->json('sections');
            // Numeric class names sort by value; anything else goes after.
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['school_id', 'name']);
        });

        // Backfill from what is already there, so nothing stops working.
        $years = DB::table('student_reports')->distinct()->orderBy('academic_year')->pluck('academic_year');
        $latest = $years->last();

        foreach ($years as $year) {
            DB::table('academic_years')->insert([
                'year' => $year,
                'is_current' => $year === $latest,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $rows = DB::table('students')
            ->select('school_id', 'class', 'section')
            ->distinct()
            ->orderBy('school_id')
            ->orderBy('class')
            ->orderBy('section')
            ->get()
            ->groupBy(fn ($row) => $row->school_id.'|'.$row->class);

        foreach ($rows as $key => $sections) {
            [$schoolId, $class] = explode('|', $key, 2);

            DB::table('school_classes')->insert([
                'school_id' => $schoolId,
                'name' => $class,
                'sections' => json_encode($sections->pluck('section')->filter()->unique()->values()->all() ?: ['A']),
                'sort_order' => is_numeric($class) ? (int) $class : 100,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('school_classes');
        Schema::dropIfExists('academic_years');
    }
};

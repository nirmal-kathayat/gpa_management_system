<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->string('exam_type'); // 1st_terminal, 2nd_terminal, final_terminal, pre_board
            $table->decimal('theory_marks', 5, 2)->nullable();
            $table->decimal('practical_marks', 5, 2)->nullable();
            $table->decimal('total_marks', 5, 2);
            $table->string('letter_grade');
            $table->decimal('grade_point', 3, 2);
            $table->string('academic_year');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_marks');
    }
};

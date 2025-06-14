<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('student_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->string('academic_year');
            $table->decimal('final_gpa', 3, 2);
            $table->string('final_grade');
            $table->integer('position')->nullable();
            $table->integer('attendance_days')->nullable();
            $table->integer('total_days')->nullable();
            $table->text('remarks')->nullable();
            $table->string('class_response')->default('A');
            $table->string('discipline')->default('A');
            $table->string('leadership')->default('A');
            $table->string('neatness')->default('A');
            $table->string('punctuality')->default('A');
            $table->string('regularity')->default('A');
            $table->string('social_conduct')->default('A');
            $table->string('sports_game')->default('B');
            $table->date('issue_date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_reports');
    }
};

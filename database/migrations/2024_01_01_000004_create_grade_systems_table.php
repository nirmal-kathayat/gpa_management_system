<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('grade_systems', function (Blueprint $table) {
            $table->id();
            $table->string('letter_grade');
            $table->decimal('grade_point', 3, 2);
            $table->integer('marks_from');
            $table->integer('marks_to');
            $table->string('remarks');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('grade_systems');
    }
};

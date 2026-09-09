<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fields a school actually keeps on a student but the table had no room for:
 * the exam symbol number, gender, when they joined, who to call besides the
 * parents, an email, a photo for the report card, and whether they are still
 * enrolled. All optional except the status, which defaults to enrolled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('symbol_number', 50)->nullable()->after('roll_number');
            $table->string('gender', 20)->nullable()->after('date_of_birth');
            $table->date('date_of_admission')->nullable()->after('gender');
            $table->string('guardian_name')->nullable()->after('mother_name');
            $table->string('guardian_phone', 20)->nullable()->after('guardian_name');
            $table->string('email')->nullable()->after('phone');
            $table->string('photo')->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('photo');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'symbol_number', 'gender', 'date_of_admission',
                'guardian_name', 'guardian_phone', 'email', 'photo', 'is_active',
            ]);
        });
    }
};

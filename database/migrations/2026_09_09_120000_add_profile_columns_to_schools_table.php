<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The school detail page shows a code, an established year, a type, a tagline
 * and a short description. All optional, so existing rows stay valid and the
 * page simply leaves out whatever has not been filled in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->after('name');
            $table->string('tagline')->nullable()->after('code');
            $table->string('established', 50)->nullable()->after('tagline');
            $table->string('type', 50)->nullable()->after('established');
            $table->text('about')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['code', 'tagline', 'established', 'type', 'about']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The school the pupil came from, and the class they were in there — two more
 * lines on the transfer certificate. Both are optional, so every certificate
 * issued before this simply has none.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transfer_certificates', function (Blueprint $table) {
            if (! Schema::hasColumn('transfer_certificates', 'previous_school_name')) {
                $table->string('previous_school_name')->nullable()->after('last_class_studied');
            }
            if (! Schema::hasColumn('transfer_certificates', 'previous_school_class')) {
                $table->string('previous_school_class')->nullable()->after('previous_school_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transfer_certificates', function (Blueprint $table) {
            foreach (['previous_school_class', 'previous_school_name'] as $column) {
                if (Schema::hasColumn('transfer_certificates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

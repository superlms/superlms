<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The transfer certificate prints the student's PEN No (kept on the student,
 * beside their APAAR ID) and their caste as typed when it is issued, in place
 * of a yes / no for Scheduled Caste / Tribe. Both are optional here, so every
 * student and certificate from before simply has none.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Beside the APAAR ID / SC-ST tick where those are there already — a
        // fresh database gets appar_id from lms:migrate, which runs after this.
        Schema::table('student_details', function (Blueprint $table) {
            if (! Schema::hasColumn('student_details', 'pen_no')) {
                $column = $table->string('pen_no', 30)->nullable();
                if (Schema::hasColumn('student_details', 'appar_id')) {
                    $column->after('appar_id');
                }
            }
        });

        Schema::table('transfer_certificates', function (Blueprint $table) {
            if (! Schema::hasColumn('transfer_certificates', 'caste')) {
                $column = $table->string('caste', 100)->nullable();
                if (Schema::hasColumn('transfer_certificates', 'is_sc_st')) {
                    $column->after('is_sc_st');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('transfer_certificates', function (Blueprint $table) {
            if (Schema::hasColumn('transfer_certificates', 'caste')) {
                $table->dropColumn('caste');
            }
        });

        Schema::table('student_details', function (Blueprint $table) {
            if (Schema::hasColumn('student_details', 'pen_no')) {
                $table->dropColumn('pen_no');
            }
        });
    }
};

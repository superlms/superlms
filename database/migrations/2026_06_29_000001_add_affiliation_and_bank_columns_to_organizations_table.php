<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The super-admin "Add School" flow writes affiliation_no / udise_number on
 * every create, and the bank-details flow writes the bank_* columns — but the
 * original organizations migration never created them (they were only added by
 * the ad-hoc `lms:migrate` command). On any deploy that runs `php artisan
 * migrate` without `lms:migrate`, those columns are missing and creating a
 * school 500s with "Unknown column 'affiliation_no'".
 *
 * This migration makes the columns part of the real migration set so they are
 * always present. Every add is guarded with hasColumn so it is a no-op where
 * lms:migrate already added them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            if (!Schema::hasColumn('organizations', 'affiliation_no')) {
                $table->string('affiliation_no')->nullable()->after('school_code');
            }
            if (!Schema::hasColumn('organizations', 'udise_number')) {
                $table->string('udise_number')->nullable()->after('affiliation_no');
            }
            if (!Schema::hasColumn('organizations', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('address');
            }
            if (!Schema::hasColumn('organizations', 'bank_account_no')) {
                $table->string('bank_account_no')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('organizations', 'bank_ifsc')) {
                $table->string('bank_ifsc')->nullable()->after('bank_account_no');
            }
            if (!Schema::hasColumn('organizations', 'bank_branch')) {
                $table->string('bank_branch')->nullable()->after('bank_ifsc');
            }
            if (!Schema::hasColumn('organizations', 'bank_holder_name')) {
                $table->string('bank_holder_name')->nullable()->after('bank_branch');
            }
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            foreach ([
                'affiliation_no', 'udise_number', 'bank_name', 'bank_account_no',
                'bank_ifsc', 'bank_branch', 'bank_holder_name',
            ] as $col) {
                if (Schema::hasColumn('organizations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

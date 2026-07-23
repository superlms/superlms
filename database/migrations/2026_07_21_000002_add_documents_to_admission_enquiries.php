<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admission_enquiries', function (Blueprint $table) {
            if (!Schema::hasColumn('admission_enquiries', 'documents')) {
                $table->json('documents')->nullable()->after('result_pdf');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admission_enquiries', function (Blueprint $table) {
            if (Schema::hasColumn('admission_enquiries', 'documents')) {
                $table->dropColumn('documents');
            }
        });
    }
};

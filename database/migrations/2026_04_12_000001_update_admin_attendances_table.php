<?php

use App\Models\Admin\AdminEmployee;
use App\Models\Organization;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('admin_attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_attendances', 'organization_id')) {
                $table->foreignIdFor(Organization::class)
                    ->nullable()
                    ->after('id');
            }

            if (!Schema::hasColumn('admin_attendances', 'admin_employee_id')) {
                $table->foreignIdFor(AdminEmployee::class)
                    ->nullable()
                    ->after('organization_id');
            }

            if (!Schema::hasColumn('admin_attendances', 'date')) {
                $table->date('date')->nullable()->after('admin_employee_id');
            }

            if (!Schema::hasColumn('admin_attendances', 'status')) {
                $table->enum('status', ['present', 'absent', 'half_day', 'leave'])
                    ->default('present')
                    ->after('date');
            }

            if (!Schema::hasColumn('admin_attendances', 'note')) {
                $table->string('note')->nullable()->after('status');
            }

            if (!Schema::hasColumn('admin_attendances', 'admin_employee_id') || !Schema::hasColumn('admin_attendances', 'date')) {
                $table->unique(['admin_employee_id', 'date'], 'unique_admin_employee_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_attendances', function (Blueprint $table) {
            if (Schema::hasColumn('admin_attendances', 'note')) {
                $table->dropColumn('note');
            }
            if (Schema::hasColumn('admin_attendances', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('admin_attendances', 'date')) {
                $table->dropColumn('date');
            }
            if (Schema::hasColumn('admin_attendances', 'admin_employee_id')) {
                $table->dropForeign(['admin_employee_id']);
                $table->dropColumn('admin_employee_id');
            }
            if (Schema::hasColumn('admin_attendances', 'organization_id')) {
                $table->dropForeign(['organization_id']);
                $table->dropColumn('organization_id');
            }
        });
    }
};

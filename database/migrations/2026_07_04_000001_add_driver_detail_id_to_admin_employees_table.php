<?php

use App\Models\Admin\DriverDetail;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_employees', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_employees', 'driver_detail_id')) {
                $table->foreignIdFor(DriverDetail::class)->nullable()->after('teacher_detail_id')->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('admin_employees', function (Blueprint $table) {
            if (Schema::hasColumn('admin_employees', 'driver_detail_id')) {
                $table->dropConstrainedForeignId('driver_detail_id');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * teacher_arrangements was created without organization_id or arranged_by,
 * even though the model and every write to this table have always expected
 * both — every save has been failing on an "unknown column" error, silently
 * swallowed by the Livewire component's try/catch, ever since this table
 * was created.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('teacher_arrangements')) {
            return;
        }

        Schema::table('teacher_arrangements', function (Blueprint $table) {
            if (!Schema::hasColumn('teacher_arrangements', 'organization_id')) {
                $table->unsignedBigInteger('organization_id')->default(0)->after('id');
            }
            if (!Schema::hasColumn('teacher_arrangements', 'arranged_by')) {
                $table->unsignedBigInteger('arranged_by')->nullable()->after('reason');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('teacher_arrangements')) {
            return;
        }

        Schema::table('teacher_arrangements', function (Blueprint $table) {
            foreach (['organization_id', 'arranged_by'] as $col) {
                if (Schema::hasColumn('teacher_arrangements', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

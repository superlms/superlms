<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transportations', function (Blueprint $table) {
            if (!Schema::hasColumn('transportations', 'drop_time')) {
                $table->string('drop_time', 20)->nullable()->after('pickup_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transportations', function (Blueprint $table) {
            if (Schema::hasColumn('transportations', 'drop_time')) {
                $table->dropColumn('drop_time');
            }
        });
    }
};

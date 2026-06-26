<?php

use App\Models\Calendar\TimeTable;
use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherDetail;
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
        Schema::create('time_table_academics', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TimeTable::class)->default(0);
            $table->foreignIdFor(Organization::class)->default(0);

            // Academic relationships
            $table->foreignIdFor(Standard::class)->default(0);
            $table->foreignIdFor(Section::class)->default(0);
            $table->foreignIdFor(Subject::class)->default(0);
            $table->foreignIdFor(TeacherDetail::class)->default(0);
            // Batch/Group info
            $table->string('batch_name')->nullable();
            $table->enum('group_type', ['whole_class', 'group_a', 'group_b', 'special'])->default('whole_class');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_table_academics');
    }
};

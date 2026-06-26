<?php

use App\Models\Admin\Exam;
use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
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
        Schema::create('exam_copies', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Organization::class)->default(0);
            $table->foreignIdFor(User::class)->default(0);
            $table->foreignIdFor(StudentDetail::class)->default(0);
            $table->foreignIdFor(Standard::class)->default(0);
            $table->foreignIdFor(Section::class)->default(0);
            $table->foreignIdFor(Subject::class)->default(0);
            $table->foreignIdFor(TeacherDetail::class)->default(0);
            $table->foreignIdFor(Exam::class)->default(0);
            $table->decimal('marks_obtained', 8, 2)->nullable(); 
            $table->decimal('max_marks', 8, 2)->nullable(); 
            $table->decimal('percentage', 5, 2)->nullable(); 
            $table->string('grade')->nullable(); 
            $table->text('remarks')->nullable();
            $table->boolean('is_absent')->default(false); 
            $table->boolean('is_recheck')->default(false); 
            $table->json('breakup')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_copies');
    }
};

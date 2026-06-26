<?php

use App\Models\Organization;
use App\Models\Student\StudentDetail;
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
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(StudentDetail::class)->default(0);
            $table->foreignIdFor(User::class)->default(0);
            $table->foreignIdFor(Organization::class)->default(0);
            $table->date('attendance_date')->nullable();
            $table->unsignedTinyInteger('status')->default(0);
            $table->string('remarks')->nullable();
            $table->unsignedBigInteger('marked_by')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};

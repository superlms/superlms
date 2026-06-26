<?php

use App\Models\Admin\TeacherTimeTable;
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
        Schema::create('teacher_arrangements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('original_teacher_id')->default(0);
            $table->unsignedBigInteger('substitute_teacher_id')->default(0);
            $table->foreignIdFor(TeacherTimeTable::class)->default(0);
            $table->date('date')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_arrangements');
    }
};

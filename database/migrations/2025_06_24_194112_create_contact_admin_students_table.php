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
        Schema::create('contact_admin_students', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(StudentDetail::class)->default(0);
            $table->foreignIdFor(User::class)->default(0);
            $table->foreignIdFor(Organization::class)->default(0);
            $table->text('topic')->nullable();
            $table->text('student_query')->nullable();
            $table->string('image')->nullable();
            $table->text('admin_text')->nullable();
            $table->boolean('admin_reply')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_admin_students');
    }
};

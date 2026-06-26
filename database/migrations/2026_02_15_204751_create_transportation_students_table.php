<?php

use App\Models\Admin\Transportation;
use App\Models\Organization;
use App\Models\Student\StudentDetail;
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
        Schema::create('transportation_students', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Transportation::class)->default(0);
            $table->foreignIdFor(StudentDetail::class)->default(0);
            $table->foreignIdFor(Organization::class)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transportation_students');
    }
};

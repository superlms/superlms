<?php

use App\Models\Organization;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
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
        Schema::create('standard_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Standard::class)->default(0); 
            $table->foreignIdFor(Subject::class)->default(0); 
            $table->foreignIdFor(Organization::class)->default(0); 
            $table->boolean('is_mandatory')->default(true); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('standard_subjects');
    }
};

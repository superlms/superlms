<?php

use App\Models\Organization;
use App\Models\Student\Section;
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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Organization::class)->default(0);
            $table->foreignIdFor(Standard::class)->default(0);
            $table->foreignIdFor(Section::class)->default(0);
            $table->foreignIdFor(Subject::class)->default(0);
            $table->string('title')->nullable();
            $table->string('book_logo')->nullable();
            $table->string('pdf_file')->nullable();
            $table->boolean('is_active')->boolean(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};

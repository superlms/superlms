<?php

use App\Models\Organization;
use App\Models\Student\Chapter;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\Topic;
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
        Schema::create('mcq_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Organization::class)->default(0);
            $table->foreignIdFor(Standard::class)->default(0);
            $table->foreignIdFor(Section::class)->default(0);
            $table->foreignIdFor(Chapter::class)->default(0);
            $table->foreignIdFor(Topic::class)->default(0);
            $table->unsignedBigInteger('created_by')->default(0);
            $table->text('question_text')->nullable();
            $table->integer('time_limit')->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mcq_questions');
    }
};

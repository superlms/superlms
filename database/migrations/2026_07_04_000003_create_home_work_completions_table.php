<?php

use App\Models\Admin\HomeWork;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A student marks a homework as done from the app → one row here. Presence of
     * a row (keyed by home_work_id + user_id) means "completed"; the admin
     * Homework Status tab reads these to colour each subject green/red.
     */
    public function up(): void
    {
        Schema::create('home_work_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Organization::class)->default(0)->index();
            $table->foreignIdFor(HomeWork::class)->index();          // home_work_id
            $table->foreignIdFor(User::class)->index();              // the student's user account
            $table->unsignedBigInteger('student_detail_id')->nullable()->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['home_work_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_work_completions');
    }
};

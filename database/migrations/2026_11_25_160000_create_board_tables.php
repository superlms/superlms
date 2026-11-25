<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * More → Boards (user's ask of 2026-11-25): a school's two fee rules — how
     * much of the fee a Class 9–12 student must have paid before the website's
     * Boards login shows their board roll number, and before it gives their
     * admit card — and each student's board roll number and admit card.
     */
    public function up(): void
    {
        if (! Schema::hasTable('board_settings')) {
            Schema::create('board_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->unique();
                $table->decimal('roll_fee_percent', 5, 2)->default(0);
                $table->decimal('admit_fee_percent', 5, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('board_students')) {
            Schema::create('board_students', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('organization_id')->index();
                $table->unsignedBigInteger('student_detail_id')->unique();
                $table->string('roll_number', 40)->nullable();
                $table->string('admit_card_path')->nullable();
                $table->string('admit_card_name')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('board_students');
        Schema::dropIfExists('board_settings');
    }
};

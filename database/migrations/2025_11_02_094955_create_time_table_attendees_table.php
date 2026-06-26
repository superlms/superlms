<?php

use App\Models\Calendar\TimeTable;
use App\Models\Organization;
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
        Schema::create('time_table_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TimeTable::class)->default(0);
            $table->foreignIdFor(Organization::class)->default(0);
            $table->foreignIdFor(User::class)->default(0);
            
            // Attendee details
            $table->enum('role', ['teacher', 'student', 'staff', 'parent', 'guest'])->default('student');
            $table->enum('attendance_status', ['scheduled', 'present', 'absent', 'late', 'leave'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamp('attended_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_table_attendees');
    }
};

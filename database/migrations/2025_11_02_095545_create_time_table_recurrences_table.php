<?php

use App\Models\Calendar\TimeTable;
use App\Models\Organization;
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
        Schema::create('time_table_recurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TimeTable::class)->default(0);
            $table->foreignIdFor(Organization::class)->default(0);
            
            // Recurrence instance
            $table->date('recurrence_date')->nullable();
            $table->boolean('is_modified')->default(false);
            $table->text('modification_notes')->nullable();
            $table->boolean('is_cancelled')->default(false);
            $table->text('cancellation_reason')->nullable();
            
            // Modified timings (if different from original)
            $table->time('modified_start_time')->nullable();
            $table->time('modified_end_time')->nullable();
            $table->foreignId('modified_location_id')->nullable()->constrained('time_table_locations')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_table_recurrences');
    }
};

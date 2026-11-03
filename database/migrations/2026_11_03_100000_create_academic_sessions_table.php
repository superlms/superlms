<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The academic sessions (school years, e.g. 2026–27) the super admin keeps
     * on Super Admin → Session; one of them is the current session, which the
     * top bar of every panel shows (App\Models\AcademicSession).
     *
     * The session the top bar showed until now — April of this year to March
     * of the next — becomes the first, and current, one: nothing on screen
     * changes when this runs.
     */
    public function up(): void
    {
        if (!Schema::hasTable('academic_sessions')) {
            Schema::create('academic_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('name', 20)->unique();
                $table->date('starts_on');
                $table->date('ends_on');
                $table->boolean('is_current')->default(false)->index();
                $table->timestamps();
            });
        }

        if (DB::table('academic_sessions')->count() === 0) {
            $year = (int) now()->year;

            DB::table('academic_sessions')->insert([
                'name'       => $year . '–' . substr((string) ($year + 1), -2),
                'starts_on'  => $year . '-04-01',
                'ends_on'    => ($year + 1) . '-03-31',
                'is_current' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_sessions');
    }
};

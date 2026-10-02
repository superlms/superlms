<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\AdminStudentController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * A student's admission date is the one entered — never the day the student
 * was added — and the dates that were only ever the day of adding are taken
 * off the students who have them.
 */
class AdmissionDateTest extends TestCase
{
    private function migration()
    {
        return require database_path('migrations/2026_10_02_040000_remove_assumed_admission_dates.php');
    }

    private function students(): void
    {
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->string('full_name');
            $t->date('date_of_admission')->nullable();
            $t->timestamps();
        });

        DB::table('student_details')->insert([
            // The day of adding, never entered.
            ['id' => 1, 'full_name' => 'Added today',     'date_of_admission' => '2026-04-10', 'created_at' => '2026-04-10 11:20:00', 'updated_at' => '2026-06-01 09:00:00'],
            // Entered by the school: earlier than the day of entry.
            ['id' => 2, 'full_name' => 'Entered',         'date_of_admission' => '2024-04-01', 'created_at' => '2026-04-10 11:25:00', 'updated_at' => '2026-04-10 11:25:00'],
            // Had none, and an edit gave it the day of the edit.
            ['id' => 3, 'full_name' => 'Edited',          'date_of_admission' => '2026-07-15', 'created_at' => '2026-04-10 11:30:00', 'updated_at' => '2026-07-15 16:00:00'],
            // No date at all.
            ['id' => 4, 'full_name' => 'None',            'date_of_admission' => null,         'created_at' => '2026-04-10 11:35:00', 'updated_at' => '2026-04-10 11:35:00'],
            // Later than the day of adding but not the day of the last edit: left alone.
            ['id' => 5, 'full_name' => 'Later, typed',    'date_of_admission' => '2026-05-02', 'created_at' => '2026-04-10 11:40:00', 'updated_at' => '2026-08-20 10:00:00'],
        ]);
    }

    public function test_the_dates_that_were_only_the_day_of_adding_are_taken_off(): void
    {
        $this->students();

        $this->migration()->up();

        $dates = DB::table('student_details')->orderBy('id')->pluck('date_of_admission', 'id')->map(fn ($d) => $d ? substr((string) $d, 0, 10) : null)->all();
        $this->assertSame([1 => null, 2 => '2024-04-01', 3 => null, 4 => null, 5 => '2026-05-02'], $dates);

        // The record does not read as edited today.
        $this->assertSame('2026-06-01 09:00:00', DB::table('student_details')->where('id', 1)->value('updated_at'));

        // What was removed is kept, to be put back if need be.
        $kept = DB::table('student_admission_dates_removed')->orderBy('student_detail_id')->get()
            ->map(fn ($r) => [$r->student_detail_id, substr((string) $r->date_of_admission, 0, 10)])->all();
        $this->assertSame([[1, '2026-04-10'], [3, '2026-07-15']], $kept);

        // Run again, it finds nothing more.
        $this->migration()->up();
        $this->assertSame(2, DB::table('student_admission_dates_removed')->count());

        // And undone, every date is back.
        $this->migration()->down();
        $dates = DB::table('student_details')->orderBy('id')->pluck('date_of_admission', 'id')->map(fn ($d) => $d ? substr((string) $d, 0, 10) : null)->all();
        $this->assertSame([1 => '2026-04-10', 2 => '2024-04-01', 3 => '2026-07-15', 4 => null, 5 => '2026-05-02'], $dates);
        $this->assertFalse(Schema::hasTable('student_admission_dates_removed'));
    }

    public function test_the_app_saves_the_date_sent_or_none(): void
    {
        $controller = app(AdminStudentController::class);
        $data = fn (array $input) => (fn () => $this->detailData(Request::create('/', 'POST', $input), 1, 8, 'ADM-1', 'R-1', 'CBSE'))->call($controller);

        $this->assertNull($data(['full_name' => 'Asha'])['date_of_admission']);
        $this->assertNull($data(['full_name' => 'Asha', 'date_of_admission' => ''])['date_of_admission']);
        $this->assertSame('2024-04-01', $data(['full_name' => 'Asha', 'date_of_admission' => '2024-04-01'])['date_of_admission']);
    }

    public function test_no_save_path_assumes_today(): void
    {
        foreach (['app/Livewire/Admin/Student.php', 'app/Livewire/SuperAdmin/Student.php', 'app/Http/Controllers/v1/AdminStudentController.php'] as $file) {
            $code = file_get_contents(base_path($file));
            $this->assertDoesNotMatchRegularExpression("/'date_of_admission'\\s*=>.*now\\(\\)/", $code, $file);
            $this->assertDoesNotMatchRegularExpression('/DateOfAdmission\\s*=\\s*now\\(\\)/', $code, $file);
        }
    }
}

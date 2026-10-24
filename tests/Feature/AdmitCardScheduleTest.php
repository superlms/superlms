<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AdmitCardController;
use App\Livewire\Admin\AdmitCard;
use App\Models\Student\AdmitCard as AdmitCardModel;
use App\Models\User;
use App\Support\AdmitCardSchedule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * An admit card prints its class's datesheet as it is now — not the copy it
 * took when it was issued. Cards issued before the datesheet was saved (Mid
 * Term 2026: issued 22 Oct, every paper a dash) get its dates once it is, and
 * a datesheet changed later reaches the cards already issued.
 */
class AdmitCardScheduleTest extends TestCase
{
    private int $org = 9;
    private int $std;
    private int $sec;
    private int $exam;
    private int $student;
    private array $subject = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('d', 32))]);

        Schema::create('organizations', function (Blueprint $t) { $t->id(); $t->string('name')->nullable(); $t->timestamps(); });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('role')->nullable(); $t->string('image')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->integer('order')->default(0); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->unsignedBigInteger('standard_id'); $t->string('name'); $t->timestamps();
        });
        Schema::create('subjects', function (Blueprint $t) { $t->id(); $t->string('name'); $t->boolean('is_active')->default(true); $t->timestamps(); });
        Schema::create('section_subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id')->nullable(); $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('section_id')->nullable();
            $t->unsignedBigInteger('subject_id'); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->string('full_name')->nullable(); $t->string('father_name')->nullable(); $t->string('mother_name')->nullable();
            $t->string('roll_no')->nullable(); $t->string('admission_no')->nullable(); $t->string('image')->nullable();
            $t->boolean('transportation_required')->default(false); $t->timestamps();
        });
        Schema::create('exams', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('exam_name'); $t->string('academic_year')->nullable();
            $t->date('start_date')->nullable(); $t->timestamps();
        });
        Schema::create('exam_datesheets', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('exam_id'); $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('section_id')->nullable(); $t->timestamps();
        });
        Schema::create('exam_datesheet_papers', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('exam_datesheet_id'); $t->unsignedBigInteger('subject_id')->nullable(); $t->date('exam_date')->nullable();
            $t->string('start_time')->nullable(); $t->string('end_time')->nullable(); $t->integer('shift')->default(1); $t->timestamps();
        });
        Schema::create('admit_cards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('student_detail_id'); $t->unsignedBigInteger('exam_id'); $t->unsignedBigInteger('organization_id');
            $t->string('admit_card_number')->nullable(); $t->string('student_name')->nullable(); $t->string('father_name')->nullable();
            $t->string('mother_name')->nullable(); $t->string('roll_number')->nullable(); $t->string('exam_roll_number')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable(); $t->string('exam_name')->nullable();
            $t->string('academic_year')->nullable(); $t->timestamp('reporting_time')->nullable(); $t->string('exam_center')->nullable();
            $t->string('exam_center_address')->nullable(); $t->text('instructions')->nullable(); $t->text('allowed_items')->nullable();
            $t->text('prohibited_items')->nullable(); $t->text('subjects')->nullable(); $t->string('status')->default('active');
            $t->string('issue_criteria', 20)->nullable(); $t->string('seat_number')->nullable(); $t->string('room_number')->nullable();
            $t->timestamp('printed_at')->nullable(); $t->date('issue_date')->nullable(); $t->unsignedBigInteger('created_by')->nullable(); $t->timestamps();
        });
        Schema::create('seating_plans', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('exam_id')->nullable(); $t->string('name')->nullable();
            $t->date('exam_date')->nullable(); $t->string('session')->nullable(); $t->timestamps();
        });
        // What the listing reads for its "Issued on" figures.
        Schema::create('student_attendances', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('student_detail_id'); $t->integer('status'); $t->date('date')->nullable(); $t->timestamps();
        });
        Schema::create('fee_structures', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->unsignedBigInteger('student_detail_id')->nullable(); $t->string('fee_type')->default('academic'); $t->decimal('amount', 10, 2)->default(0);
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id'); $t->decimal('amount', 10, 2)->default(0); $t->timestamps();
        });
        app()->forgetInstance('fee_structures.student_rows');

        DB::table('organizations')->insert(['id' => $this->org, 'name' => 'Adarsh Satyadev Inter College']);
        $this->actingAs(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]));

        $this->std  = DB::table('standards')->insertGetId(['organization_id' => $this->org, 'name' => 'CLASS 8', 'order' => 8]);
        $this->sec  = DB::table('sections')->insertGetId(['organization_id' => $this->org, 'standard_id' => $this->std, 'name' => 'SECTION A']);
        $this->exam = DB::table('exams')->insertGetId(['organization_id' => $this->org, 'exam_name' => 'Mid Term Examination', 'academic_year' => '2026-2027']);
        // Sanskrit is taught in the class but not on the datesheet.
        foreach (['ENGLISH', 'MATHEMATICS', 'COMPUTER', 'MORAL EDUCATION & GK', 'SANSKRIT'] as $name) {
            $id = DB::table('subjects')->insertGetId(['name' => $name]);
            DB::table('section_subjects')->insert(['organization_id' => $this->org, 'standard_id' => $this->std, 'section_id' => $this->sec, 'subject_id' => $id]);
            $this->subject[$name] = $id;
        }
        $this->student = DB::table('student_details')->insertGetId(['organization_id' => $this->org, 'standard_id' => $this->std,
            'section_id' => $this->sec, 'full_name' => 'Aarti', 'father_name' => 'Netrapal Singh', 'roll_no' => '216']);
    }

    /** The datesheet as the Seating Plan saves it: Computer and Moral Education share a slot. */
    private function datesheet(?int $sectionId = null, ?array $papers = null): int
    {
        $sheet = DB::table('exam_datesheets')->insertGetId(['organization_id' => $this->org, 'exam_id' => $this->exam,
            'standard_id' => $this->std, 'section_id' => $sectionId]);
        foreach ($papers ?? [
            'ENGLISH'             => '2026-10-27',
            'MATHEMATICS'         => '2026-10-29',
            'COMPUTER'            => '2026-11-04',
            'MORAL EDUCATION & GK' => '2026-11-04',
        ] as $name => $date) {
            DB::table('exam_datesheet_papers')->insert(['exam_datesheet_id' => $sheet, 'subject_id' => $this->subject[$name],
                'exam_date' => $date, 'start_time' => '09:00:00', 'end_time' => '12:00:00', 'shift' => 1]);
        }

        return $sheet;
    }

    private function issue(): int
    {
        Livewire::test(AdmitCard::class)
            ->set('examFilter', (string) $this->exam)
            ->set('standardFilter', (string) $this->std)
            ->call('issueOne', $this->student);

        return (int) DB::table('admit_cards')->where('student_detail_id', $this->student)->value('id');
    }

    private function stored(int $cardId): array
    {
        return json_decode(DB::table('admit_cards')->where('id', $cardId)->value('subjects'), true);
    }

    /** The four-up print sheet, as Print opens it, as text. */
    private function printed(int $cardId): string
    {
        $html = app(AdmitCardController::class)
            ->printAll(Request::create('/admit-card/print-all', 'GET', ['ids' => (string) $cardId]), $this->org)
            ->render();

        return preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5));
    }

    public function test_a_card_issued_before_the_datesheet_prints_it_once_it_is_saved(): void
    {
        $card = $this->issue();

        // Issued with no datesheet: the class's subjects, every date blank.
        $this->assertSame(['', '', '', '', ''], array_column($this->stored($card), 'exam_date'));

        $this->datesheet($this->sec);
        $text = $this->printed($card);

        $this->assertStringContainsString('ENGLISH 27 Oct 2026, Tue 9:00 AM – 12:00 PM', $text);
        $this->assertStringContainsString('MATHEMATICS 29 Oct 2026, Thu 9:00 AM – 12:00 PM', $text);
        $this->assertStringContainsString('COMPUTER 04 Nov 2026, Wed 9:00 AM – 12:00 PM', $text);
        $this->assertStringContainsString('MORAL EDUCATION & GK 04 Nov 2026, Wed 9:00 AM – 12:00 PM', $text);
        // The papers are the datesheet's: a subject it does not set is not on the card.
        $this->assertStringNotContainsString('SANSKRIT', $text);

        // And the card keeps it, so the apps read the same.
        $rows = collect($this->stored($card))->keyBy('subject_name');
        $this->assertSame(['ENGLISH', 'MATHEMATICS', 'COMPUTER', 'MORAL EDUCATION & GK'], $rows->keys()->all());
        $this->assertSame('2026-10-27', $rows['ENGLISH']['exam_date']);
        $this->assertSame('09:00', $rows['ENGLISH']['exam_time']);
        $this->assertSame('12:00', $rows['ENGLISH']['exam_end_time']);
        $this->assertSame('3 Hours', $rows['ENGLISH']['exam_duration']);
        $this->assertSame('eligible', $rows['ENGLISH']['status']);
    }

    public function test_a_datesheet_changed_after_issue_reaches_the_card(): void
    {
        $sheet = $this->datesheet();
        $card  = $this->issue();
        $this->assertSame('2026-10-29', collect($this->stored($card))->firstWhere('subject_name', 'MATHEMATICS')['exam_date']);

        DB::table('exam_datesheet_papers')->where('exam_datesheet_id', $sheet)->where('subject_id', $this->subject['MATHEMATICS'])
            ->update(['exam_date' => '2026-10-30']);

        $this->assertStringContainsString('MATHEMATICS 30 Oct 2026, Fri', $this->printed($card));
        $this->assertSame('2026-10-30', collect($this->stored($card))->firstWhere('subject_name', 'MATHEMATICS')['exam_date']);
    }

    public function test_a_class_with_no_datesheet_keeps_the_card_it_has(): void
    {
        $card   = $this->issue();
        $before = $this->stored($card);

        AdmitCardSchedule::refresh([AdmitCardModel::find($card)]);

        $this->assertSame($before, $this->stored($card));
        $this->assertCount(5, $before);
    }

    public function test_an_empty_section_sheet_does_not_hide_the_class_wide_one(): void
    {
        DB::table('exam_datesheets')->insert(['organization_id' => $this->org, 'exam_id' => $this->exam,
            'standard_id' => $this->std, 'section_id' => $this->sec]);   // the section's own, with no papers
        $this->datesheet(null);                                           // the class's, with papers

        $rows = AdmitCardSchedule::forNewCard($this->org, $this->exam, $this->std, $this->sec);

        $this->assertSame(['2026-10-27', '2026-10-29', '2026-11-04', '2026-11-04'], array_column($rows, 'exam_date'));
    }

    public function test_what_a_card_carried_of_its_own_stays_with_its_subject(): void
    {
        $card = $this->issue();
        $rows = $this->stored($card);
        $rows[0]['total_marks'] = 80;   // English
        DB::table('admit_cards')->where('id', $card)->update(['subjects' => json_encode($rows)]);

        $this->datesheet($this->sec);
        AdmitCardSchedule::refresh([AdmitCardModel::find($card)]);

        $english = collect($this->stored($card))->firstWhere('subject_name', 'ENGLISH');
        $this->assertSame(80, $english['total_marks']);
        $this->assertSame('2026-10-27', $english['exam_date']);
    }
}

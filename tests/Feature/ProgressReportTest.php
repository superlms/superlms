<?php

namespace Tests\Feature;

use App\Livewire\Admin\Performance;
use App\Models\Admin\Exam;
use App\Models\User;
use App\Support\ProgressReport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Performance → Progress Report: one exam's report card for each student of
 * a section — every subject of the exam with its marks and grade, the total,
 * percentage, grade, rank (equal marks share one) and result — as an A5 PDF,
 * a card to a page. A deleted student's marks, another section's and another
 * school's are left out.
 */
class ProgressReportTest extends TestCase
{
    protected array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('k', 32))]);

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->boolean('is_active')->default(true);
            $t->text('image')->nullable(); $t->timestamps();
        });
        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('mobile_number')->nullable();
            $t->text('address')->nullable(); $t->text('logo')->nullable(); $t->string('affiliation_no')->nullable();
            $t->timestamps();
        });
        Schema::create('school_infos', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('website_url')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name');
            $t->integer('order')->default(0); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('name'); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name');
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('exams', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('exam_name')->nullable();
            $t->string('academic_year')->nullable(); $t->boolean('is_published')->default(true);
            $t->date('start_date')->nullable(); $t->date('end_date')->nullable(); $t->integer('total_marks')->nullable();
            $t->timestamps();
        });
        Schema::create('exam_copies', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('exam_id')->nullable();
            $t->unsignedBigInteger('student_detail_id')->nullable(); $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable(); $t->unsignedBigInteger('subject_id')->nullable();
            $t->decimal('marks_obtained', 6, 2)->nullable(); $t->integer('max_marks')->nullable();
            $t->decimal('percentage', 5, 2)->nullable(); $t->string('grade')->nullable();
            $t->boolean('is_absent')->default(false); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->nullable(); $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('section_id');
            $t->string('full_name')->nullable(); $t->string('father_name')->nullable(); $t->string('mother_name')->nullable();
            $t->date('dob')->nullable(); $t->string('admission_no')->nullable(); $t->string('roll_no')->nullable();
            $t->string('image')->nullable(); $t->timestamps();
        });

        DB::table('organizations')->insert([
            ['id' => 6, 'name' => 'Sunrise Public School', 'mobile_number' => '9876543210', 'address' => 'Station Road, Kota', 'affiliation_no' => '1730123'],
            ['id' => 7, 'name' => 'Other School', 'mobile_number' => null, 'address' => null, 'affiliation_no' => null],
        ]);
        $five = DB::table('standards')->insertGetId(['organization_id' => 6, 'name' => '5', 'order' => 1]);
        $a    = DB::table('sections')->insertGetId(['standard_id' => $five, 'organization_id' => 6, 'name' => 'A']);
        $b    = DB::table('sections')->insertGetId(['standard_id' => $five, 'organization_id' => 6, 'name' => 'B']);
        $sub  = [];
        foreach (['Hindi', 'English', 'Maths', 'Science'] as $name) {
            $sub[$name] = DB::table('subjects')->insertGetId(['organization_id' => 6, 'name' => $name]);
        }
        $exam  = DB::table('exams')->insertGetId(['organization_id' => 6, 'exam_name' => 'Half Yearly', 'total_marks' => 50,
                                                  'start_date' => '2026-09-12', 'end_date' => '2026-09-20']);
        $other = DB::table('exams')->insertGetId(['organization_id' => 7, 'exam_name' => 'Elsewhere', 'total_marks' => 50]);

        $kid = fn (string $name, int $section, array $more = []) => DB::table('student_details')->insertGetId($more + [
            'organization_id' => 6, 'standard_id' => $five, 'section_id' => $section, 'full_name' => $name,
            'admission_no' => 'ADM-' . strtoupper(substr($name, 0, 3)),
        ]);
        // Out of A to Z on purpose: the list and the PDF read A to Z.
        $diya   = $kid('Diya', $a, ['father_name' => 'Ravi', 'mother_name' => 'Sunita', 'dob' => '2016-04-05', 'roll_no' => '4']);
        $chetan = $kid('Chetan', $a);
        $aarav  = $kid('Aarav', $a);
        $bhavya = $kid('Bhavya', $a);
        $esha   = $kid('Esha', $b);

        $mark = function (int $student, string $subject, ?float $got, int $section = 0) use ($exam, $five, $a, $sub) {
            DB::table('exam_copies')->insert([
                'organization_id' => 6, 'exam_id' => $exam, 'standard_id' => $five, 'section_id' => $section ?: $a,
                'subject_id' => $sub[$subject], 'student_detail_id' => $student, 'max_marks' => 50,
                'marks_obtained' => $got ?? 0, 'percentage' => $got === null ? 0 : $got / 50 * 100,
                'is_absent' => $got === null,
            ]);
        };
        // Aarav and Bhavya both get 115 of 150: they share rank 1.
        $mark($aarav, 'Hindi', 45);  $mark($aarav, 'English', 40);  $mark($aarav, 'Maths', 30);
        $mark($bhavya, 'Hindi', 40); $mark($bhavya, 'English', 45); $mark($bhavya, 'Maths', 30);
        // Chetan: 10 of 50 in Hindi (below the pass mark) and absent in English.
        $mark($chetan, 'Hindi', 10); $mark($chetan, 'English', null); $mark($chetan, 'Maths', 50);
        // Diya: no Maths marks yet.
        $mark($diya, 'Hindi', 50);   $mark($diya, 'English', 50);
        // A deleted student's marks stay behind; Esha is in section B.
        $mark(999, 'Hindi', 50);
        $mark($esha, 'Hindi', 50, $b);

        $this->ids = compact('five', 'a', 'b', 'exam', 'other', 'diya', 'chetan', 'aarav', 'bhavya', 'esha') + ['sub' => $sub];

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 1;
        $admin->organization_id = 6;
        $admin->role = 'admin';
        $this->actingAs($admin);
    }

    protected function params(array $more = []): array
    {
        return array_merge(['organization' => 6, 'exam' => $this->ids['exam'], 'standard' => $this->ids['five'], 'section' => $this->ids['a']], $more);
    }

    private static function pages(string $pdf): int
    {
        return preg_match_all('#/Type\s*/Page(?![s\w])#', $pdf);
    }

    public function test_the_cards_are_every_subject_of_the_exam_ranked_and_a_to_z(): void
    {
        $cards = ProgressReport::cards(6, Exam::find($this->ids['exam']), $this->ids['five'], $this->ids['a']);

        $this->assertSame(['Aarav', 'Bhavya', 'Chetan', 'Diya'], array_column($cards, 'name'));
        $by = array_column($cards, null, 'name');

        // Equal marks share a rank; the next one is counted on (1, 1, 3, 4).
        $this->assertSame([1, 1, 4, 3], array_map(fn ($n) => $by[$n]['rank'], ['Aarav', 'Bhavya', 'Chetan', 'Diya']));
        $this->assertSame(4, $by['Aarav']['of']);

        // The exam's subjects for the section, A to Z; Science has no marks, so it is not on the card.
        $this->assertSame(['English', 'Hindi', 'Maths'], array_column($by['Aarav']['rows'], 'subject'));
        $this->assertSame('115', $by['Aarav']['obtained']);
        $this->assertSame('150', $by['Aarav']['max']);
        $this->assertSame(76.67, $by['Aarav']['pct']);
        $this->assertSame('A', $by['Aarav']['grade']);
        $this->assertSame('Pass', $by['Aarav']['result']);
        $this->assertSame('40', $by['Aarav']['rows'][0]['obtained']);
        $this->assertSame('C', $by['Aarav']['rows'][2]['grade']); // Maths 30 of 50 = 60%
    }

    public function test_absent_and_not_entered_papers_and_the_result(): void
    {
        $cards = ProgressReport::cards(6, Exam::find($this->ids['exam']), $this->ids['five'], $this->ids['a']);
        $by = array_column($cards, null, 'name');

        // Absent counts as 0 of its maximum; a paper below the pass mark fails the card.
        $english = $by['Chetan']['rows'][0];
        $this->assertTrue($english['absent']);
        $this->assertSame('AB', $english['grade']);
        $this->assertSame('60', $by['Chetan']['obtained']);
        $this->assertSame('150', $by['Chetan']['max']);
        $this->assertSame(40.0, $by['Chetan']['pct']);
        $this->assertSame('Fail', $by['Chetan']['result']);
        // 40% is a "Pass" on the scale, but a failed paper is what the remark names.
        $this->assertSame('Needs improvement in Hindi', $by['Chetan']['remark']);

        // A paper not entered yet is on the card, empty, and not in the total.
        $maths = $by['Diya']['rows'][2];
        $this->assertSame('Maths', $maths['subject']);
        $this->assertFalse($maths['entered']);
        $this->assertSame(2, $by['Diya']['entered']);
        $this->assertSame(3, $by['Diya']['subjects']);
        $this->assertSame('100', $by['Diya']['max']);
        $this->assertSame('O', $by['Diya']['grade']);
        $this->assertSame('Outstanding', $by['Diya']['remark']);
        $this->assertSame('05/04/2016', $by['Diya']['dob']);
    }

    public function test_the_progress_report_tab_lists_the_section_and_shows_a_card(): void
    {
        $page = Livewire::test(Performance::class)
            ->call('showTab', 'progress')
            ->assertSee('Progress Report')
            ->assertSee('Pick Exam → Class → Section to prepare the report cards.')
            ->set('prExam', (string) $this->ids['exam'])
            ->set('prStandard', (string) $this->ids['five'])
            ->set('prSection', (string) $this->ids['a'])
            ->assertSee('Report cards · Half Yearly')
            ->assertSee('Download All (4)')
            ->assertSee(route('admin.performance.progress-report.download', $this->params()))
            ->assertDontSee('Esha');

        $this->assertSame(4, preg_match_all('/wire:key="pr-\d+"/', $page->html()));
        $this->assertStringContainsString('1 not entered', $page->html()); // Diya's Maths

        // One student: only their row, and no Download All.
        $page->set('prStudent', (string) $this->ids['diya'])->assertDontSee('Download All');
        $this->assertSame(1, preg_match_all('/wire:key="pr-\d+"/', $page->html()));

        // View shows the A5 card itself in a frame, with its download.
        $page->call('viewProgressReport', $this->ids['diya'])
            ->assertSee(route('admin.performance.progress-report.view', $this->params(['student' => $this->ids['diya']])))
            ->assertSee(route('admin.performance.progress-report.download', $this->params(['student' => $this->ids['diya']])))
            ->call('closeProgressView')
            ->assertDontSee(route('admin.performance.progress-report.view', $this->params(['student' => $this->ids['diya']])));

        // Another class drops the section and its list.
        $page->set('prStandard', '')->assertSet('prSection', '')->assertSet('prStudent', '')
            ->assertSee('Pick Exam → Class → Section to prepare the report cards.');
    }

    public function test_the_sheet_prints_the_school_the_exam_and_the_student(): void
    {
        $exam  = Exam::find($this->ids['exam']);
        $head  = ProgressReport::head(\App\Models\Organization::find(6), $exam,
            \App\Models\Student\Standard::find($this->ids['five']), \App\Models\Student\Section::find($this->ids['a']));
        $cards = ProgressReport::cards(6, $exam, $this->ids['five'], $this->ids['a']);
        $html  = view('pdf.progress-report', ['head' => $head, 'cards' => $cards, 'fontCss' => ''])->render();

        foreach (['Sunrise Public School', 'Station Road, Kota', 'Ph: 9876543210', 'Affiliation No: 1730123',
                  'PROGRESS REPORT', 'Half Yearly  ·  Session 2026-2027', '12/09/2026 - 20/09/2026',
                  'Grand Total', 'Rank in Class', 'Grading Scale:', 'O 91-100', 'Class Teacher', 'Principal',
                  'Diya', 'Ravi', 'Sunita', 'ADM-DIY'] as $text) {
            $this->assertStringContainsString(e($text), $html, $text);
        }
        $this->assertSame(4, substr_count($html, 'class="card '));
    }

    public function test_the_pdf_is_a_card_to_each_a5_page(): void
    {
        $this->withoutMiddleware();

        $all = $this->get(route('admin.performance.progress-report.download', $this->params()));
        $all->assertOk();
        $this->assertSame('application/pdf', $all->headers->get('content-type'));
        $this->assertStringContainsString('progress_report_half_yearly_5_a.pdf', (string) $all->headers->get('content-disposition'));
        $this->assertSame(4, self::pages($all->getContent()));
        // A5 portrait: 148 x 210 mm is 419.53 x 595.28 pt.
        $this->assertMatchesRegularExpression('#/MediaBox\s*\[\s*0\.?0*\s+0\.?0*\s+419\.5\d*\s+595\.2\d*\s*\]#', $all->getContent());

        $one = $this->get(route('admin.performance.progress-report.view', $this->params(['student' => $this->ids['diya']])));
        $one->assertOk();
        $this->assertStringContainsString('inline', (string) $one->headers->get('content-disposition'));
        $this->assertStringContainsString('progress_report_half_yearly_diya.pdf', (string) $one->headers->get('content-disposition'));
        $this->assertSame(1, self::pages($one->getContent()));

        // Another school's exam, another section's student, or nothing picked: no card.
        $this->get(route('admin.performance.progress-report.download', $this->params(['exam' => $this->ids['other']])))->assertNotFound();
        $this->get(route('admin.performance.progress-report.download', $this->params(['student' => $this->ids['esha']])))->assertNotFound();
        $this->get(route('admin.performance.progress-report.download', ['organization' => 6]))->assertNotFound();
    }

    public function test_a_card_with_many_subjects_still_fits_its_page(): void
    {
        $exam = DB::table('exams')->insertGetId(['organization_id' => 6, 'exam_name' => 'Annual Examination', 'total_marks' => 100,
                                                 'academic_year' => '2026-27', 'start_date' => '2027-03-01']);
        DB::table('organizations')->where('id', 6)->update([
            'address' => 'Plot 14, Behind the Old Grain Market, Station Road, Kota, Rajasthan 324002',
            'email'   => 'office@sunrisepublicschool.example',
        ]);
        foreach ([8, 12, 16] as $count) {
            DB::table('exam_copies')->where('exam_id', $exam)->delete();
            for ($i = 1; $i <= $count; $i++) {
                $s = DB::table('subjects')->insertGetId(['organization_id' => 6, 'name' => "Subject {$count}-{$i} Literature and Language"]);
                DB::table('exam_copies')->insert([
                    'organization_id' => 6, 'exam_id' => $exam, 'standard_id' => $this->ids['five'], 'section_id' => $this->ids['a'],
                    'subject_id' => $s, 'student_detail_id' => $this->ids['aarav'], 'max_marks' => 100,
                    'marks_obtained' => 50 + $i, 'percentage' => 50 + $i, 'is_absent' => false,
                ]);
            }
            $e     = Exam::find($exam);
            $head  = ProgressReport::head(\App\Models\Organization::find(6), $e,
                \App\Models\Student\Standard::find($this->ids['five']), \App\Models\Student\Section::find($this->ids['a']));
            $cards = ProgressReport::cards(6, $e, $this->ids['five'], $this->ids['a']);
            $this->assertCount($count, $cards[0]['rows']);
            $this->assertSame('2026-27', $head['session']);
            $this->assertSame(1, self::pages(ProgressReport::pdf($head, $cards)->output()), "{$count} subjects");
        }
    }
}

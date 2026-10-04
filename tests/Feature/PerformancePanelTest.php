<?php

namespace Tests\Feature;

use App\Livewire\Admin\Performance;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Performance page: a deleted student's marks are neither listed nor
 * ranked nor counted; the header's figures follow the filters (total students,
 * active, average marks, average %); a performer's View shows the exam, class
 * and section and their marks subject by subject; Upload Marks has no remark
 * box and shows a saved mark as typed.
 */
class PerformancePanelTest extends TestCase
{
    protected array $p = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('k', 32))]);

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('name');
            $t->integer('order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('name');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('subjects', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('name');
            $t->string('code')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('standard_subjects', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('subject_id');
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('section_subjects', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('section_id');
            $t->unsignedBigInteger('subject_id');
            $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });

        $std = Standard::create(['organization_id' => 6, 'name' => '5', 'order' => 1]);
        $sec = Section::create(['standard_id' => $std->id, 'organization_id' => 6, 'name' => 'A']);
        foreach (['Hindi', 'English'] as $name) {
            $s = Subject::create(['organization_id' => 6, 'name' => $name]);
            DB::table('standard_subjects')->insert(['standard_id' => $std->id, 'subject_id' => $s->id, 'organization_id' => 6]);
            DB::table('section_subjects')->insert(['section_id' => $sec->id, 'subject_id' => $s->id, 'standard_id' => $std->id, 'organization_id' => 6]);
        }

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 1;
        $admin->organization_id = 6;
        $admin->role = 'admin';
        $this->actingAs($admin);
        Schema::create('exams', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('exam_name')->nullable();
            $t->boolean('is_published')->default(true);
            $t->date('start_date')->nullable();
            $t->integer('total_marks')->nullable();
            $t->timestamps();
        });
        Schema::create('exam_copies', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('exam_id')->nullable();
            $t->unsignedBigInteger('student_detail_id')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->decimal('marks_obtained', 6, 2)->nullable();
            $t->integer('max_marks')->nullable();
            $t->decimal('percentage', 5, 2)->nullable();
            $t->string('grade')->nullable();
            $t->string('remarks')->nullable();
            $t->boolean('is_absent')->default(false);
            $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('section_id');
            $t->string('full_name')->nullable();
            $t->string('father_name')->nullable();
            $t->string('admission_no')->nullable();
            $t->string('roll_no')->nullable();
            $t->string('image')->nullable();
            $t->timestamps();
        });

        $five  = Standard::where('name', '5')->first();
        $a     = Section::where('standard_id', $five->id)->where('name', 'A')->first();
        $hindi = Subject::where('name', 'Hindi')->first();
        $eng   = Subject::where('name', 'English')->first();
        $exam  = DB::table('exams')->insertGetId(['organization_id' => 6, 'exam_name' => 'Half Yearly', 'total_marks' => 20]);
        $this->p = ['five' => $five->id, 'a' => $a->id, 'hindi' => $hindi->id, 'eng' => $eng->id, 'exam' => $exam];

        $copy = fn ($sid, $sub, $marks, $absent = false) => DB::table('exam_copies')->insert([
            'organization_id' => 6, 'exam_id' => $exam, 'student_detail_id' => $sid, 'standard_id' => $five->id, 'section_id' => $a->id,
            'subject_id' => $sub, 'marks_obtained' => $absent ? null : $marks, 'max_marks' => 20,
            'percentage' => $absent ? 0 : round($marks / 20 * 100, 2), 'is_absent' => $absent,
        ]);
        foreach ([['Asha', 'Ram', 'A-1', true, 17.2, 18], ['Bina', 'Shyam', 'A-2', false, 12, null]] as [$name, $father, $adm, $active, $h, $e]) {
            $uid = DB::table('users')->insertGetId(['name' => $name, 'organization_id' => 6, 'role' => 'user', 'is_active' => $active]);
            $sid = DB::table('student_details')->insertGetId(['user_id' => $uid, 'organization_id' => 6, 'standard_id' => $five->id,
                'section_id' => $a->id, 'full_name' => $name, 'father_name' => $father, 'admission_no' => $adm]);
            $this->p[$name] = $sid;
            $copy($sid, $hindi->id, $h);
            $copy($sid, $eng->id, $e ?? 0, $e === null);
        }
        // A deleted student's paper, left behind.
        $copy(999, $hindi->id, 2);
    }

    public function test_the_list_leaves_out_deleted_students_and_shows_the_admission_number_under_the_name(): void
    {
        Livewire::test(Performance::class)
            ->set('filterExam', (string) $this->p['exam'])->set('filterStandard', (string) $this->p['five'])
            ->set('filterSection', (string) $this->p['a'])->set('filterSubject', (string) $this->p['hindi'])
            ->assertViewHas('examCopies', fn ($list) => $list->total() === 2)
            ->assertDontSee('N/A')
            ->assertSee('A-1')
            ->assertDontSee('Ram')
            ->assertDontSee('Admission No');
    }

    public function test_the_header_follows_the_filters(): void
    {
        $page = Livewire::test(Performance::class)
            // The whole school: both students, one active; the papers sat (the deleted
            // student's and the absent one left out): 17.2, 18, 12 → 15.7 marks, 78.7 %.
            ->assertViewHas('headStats', fn ($h) => $h['total'] === 2 && $h['active'] === 1 && $h['avg_marks'] === 15.7 && $h['avg_pct'] === 78.7)
            ->assertSee('Total Students')
            ->assertSee('Avg Marks');

        $page->set('filterExam', (string) $this->p['exam'])->set('filterStandard', (string) $this->p['five'])
            ->set('filterSection', (string) $this->p['a'])->set('filterSubject', (string) $this->p['hindi'])
            ->assertViewHas('headStats', fn ($h) => $h['avg_marks'] === 14.6 && $h['avg_pct'] === 73.0)
            ->set('filterStudent', (string) $this->p['Bina'])
            ->assertViewHas('headStats', fn ($h) => $h['total'] === 1 && $h['active'] === 0 && $h['avg_marks'] === 12.0);
    }

    public function test_a_performers_view_shows_the_exam_class_section_and_marks_by_subject(): void
    {
        Livewire::test(Performance::class)
            ->call('showTab', 'performers')
            ->set('perfExam', (string) $this->p['exam'])->set('perfStandard', (string) $this->p['five'])->set('perfSection', (string) $this->p['a'])
            ->assertSet('performers', fn ($p) => count($p) === 2)
            ->assertDontSee('Remark')
            ->assertSeeHtml('wire:click="viewPerformer(' . $this->p['Asha'] . ')"')
            ->call('viewPerformer', $this->p['Asha'])
            ->assertSet('showPerformerView', true)
            ->assertSee('Half Yearly')
            ->assertSee('Marks by subject')
            ->assertSet('performerView', fn ($v) => $v['class'] === '5' && $v['section'] === 'A' && $v['rank'] === 1
                && array_column($v['subjects'], 'subject') === ['English', 'Hindi']
                && array_column($v['subjects'], 'obtained') === ['18', '17.2']
                && $v['obtained'] === '35.2' && $v['max'] === '40' && $v['pct'] === '88')
            ->call('closePerformerView')
            ->assertSet('showPerformerView', false);
    }

    public function test_upload_marks_has_no_remark_box_and_shows_a_saved_mark_as_typed(): void
    {
        Livewire::test(Performance::class)
            ->call('openUploadMarks')
            ->set('uploadExam', (string) $this->p['exam'])->set('uploadStandard', (string) $this->p['five'])
            ->set('uploadSection', (string) $this->p['a'])->set('uploadSubject', (string) $this->p['hindi'])
            ->assertSet('studentMarks.' . $this->p['Asha'] . '.marks_obtained', '17.2')
            ->assertSeeHtml('wire:model="studentMarks.' . $this->p['Asha'] . '.marks_obtained"')
            ->assertDontSeeHtml('.remarks"');
    }
}

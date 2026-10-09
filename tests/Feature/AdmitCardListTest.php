<?php

namespace Tests\Feature;

use App\Livewire\Accounts\AdmitCard as AccountsAdmitCard;
use App\Livewire\Admin\AdmitCard;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admit Card list in the Students list's look: no Roll column, plain-text
 * status, icon actions, View as the TC page's PDF panel, Inactive in Delete's
 * place, an Issue icon for the not-issued, and "Issued on" — the fee paid %,
 * the attendance % or both, by what the card was issued on.
 */
class AdmitCardListTest extends TestCase
{
    private int $org = 6;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('a', 32))]);

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
            $t->string('mother_name')->nullable(); $t->string('roll_number')->nullable(); $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable(); $t->string('exam_name')->nullable(); $t->string('academic_year')->nullable();
            $t->timestamp('reporting_time')->nullable(); $t->string('exam_center')->nullable(); $t->string('exam_center_address')->nullable();
            $t->text('instructions')->nullable(); $t->text('allowed_items')->nullable(); $t->text('prohibited_items')->nullable();
            $t->text('subjects')->nullable(); $t->string('status')->default('active'); $t->string('issue_criteria', 20)->nullable();
            $t->timestamp('printed_at')->nullable(); $t->date('issue_date')->nullable(); $t->unsignedBigInteger('created_by')->nullable(); $t->timestamps();
        });
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

        DB::table('organizations')->insert(['id' => $this->org, 'name' => 'Demo School']);
        $this->actingAs(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]));
    }

    /** One class of three: Asha (card on fee), Bilal (card for everyone, before criteria were kept), Chand (none). */
    private function school(): array
    {
        $std  = DB::table('standards')->insertGetId(['organization_id' => $this->org, 'name' => 'NURSERY']);
        $sec  = DB::table('sections')->insertGetId(['organization_id' => $this->org, 'standard_id' => $std, 'name' => 'SECTION A']);
        $exam = DB::table('exams')->insertGetId(['organization_id' => $this->org, 'exam_name' => 'Unit Test', 'academic_year' => '2026-27']);
        $kid  = fn ($name, $father) => DB::table('student_details')->insertGetId(['organization_id' => $this->org, 'standard_id' => $std, 'section_id' => $sec,
            'full_name' => $name, 'father_name' => $father, 'roll_no' => 'R-' . $name]);
        [$asha, $bilal, $chand] = [$kid('Asha', 'Mr Rao'), $kid('Bilal', 'Mr Khan'), $kid('Chand', 'Mr Das')];

        // Fees: 1,000 for the class; Asha paid 800, Bilal 250.
        DB::table('fee_structures')->insert(['organization_id' => $this->org, 'standard_id' => $std, 'fee_type' => 'academic', 'amount' => 1000]);
        DB::table('fee_payments')->insert([['organization_id' => $this->org, 'student_detail_id' => $asha, 'amount' => 800],
            ['organization_id' => $this->org, 'student_detail_id' => $bilal, 'amount' => 250]]);
        // Attendance: Asha 3 of 4 days, Bilal 1 of 2.
        foreach ([[$asha, 1], [$asha, 1], [$asha, 1], [$asha, 0], [$bilal, 1], [$bilal, 0]] as [$id, $st]) {
            DB::table('student_attendances')->insert(['student_detail_id' => $id, 'status' => $st]);
        }

        $card = fn ($sid, $name, $criteria) => DB::table('admit_cards')->insertGetId(['student_detail_id' => $sid, 'exam_id' => $exam, 'organization_id' => $this->org,
            'admit_card_number' => 'ADMIT-' . $name, 'student_name' => $name, 'roll_number' => 'R-' . $name, 'standard_id' => $std, 'section_id' => $sec,
            'exam_name' => 'Unit Test', 'status' => 'active', 'issue_criteria' => $criteria]);

        return ['std' => $std, 'sec' => $sec, 'exam' => $exam, 'asha' => $asha, 'bilal' => $bilal, 'chand' => $chand,
            'ashaCard' => $card($asha, 'Asha', 'fee'), 'bilalCard' => $card($bilal, 'Bilal', null)];
    }

    private function page(array $s, string $class = AdmitCard::class)
    {
        return Livewire::test($class)->set('examFilter', (string) $s['exam'])->set('standardFilter', (string) $s['std']);
    }

    public function test_list_reads_as_students_with_issued_on_figures(): void
    {
        $s    = $this->school();
        DB::table('admit_cards')->where('id', $s['ashaCard'])->update(['printed_at' => now()]);
        $html = $this->page($s)->html();

        // Actions say nothing of printing and carry no status dot.
        $this->assertStringNotContainsString('wire:click="markUnprinted(', $html);
        $this->assertStringNotContainsString('rounded-full flex-shrink-0 mr-1', $html);
        $this->assertStringContainsString('wire:click="printOne(' . $s['ashaCard'] . ')"', $html);

        $this->assertStringContainsString('>S.No</th>', $html);
        $this->assertStringContainsString('>Issued on</th>', $html);
        $this->assertStringNotContainsString('>Roll</th>', $html);
        $this->assertStringNotContainsString('>Card No.</th>', $html);
        $this->assertStringNotContainsString('ADMIT-Asha', $html);                              // no card number
        $this->assertStringContainsString('NURSERY-A', $html);
        $this->assertStringContainsString('Mr Rao', $html);                                     // father's name under the name

        $this->assertMatchesRegularExpression('/Fee 80%\s*(<!--\[if ENDBLOCK\]><!\[endif\]-->)?\s*<\/td>/', $html);   // Asha: issued on fee
        $this->assertMatchesRegularExpression('/Fee 25% \/ Attendance 50%/', $html);           // Bilal: no criteria kept → both
        $this->assertStringNotContainsString('Attendance 75%', $html);                          // Asha's attendance isn't the rule

        $this->assertStringNotContainsString('rounded-full uppercase tracking-wide', $html);   // status as plain text
        $this->assertStringContainsString('<p class="text-sm text-emerald-600">Issued</p>', $html);
        $this->assertStringContainsString('<p class="text-sm text-amber-600">Not issued</p>', $html);
        $this->assertStringNotContainsString('rounded-md border border-gray-200 text-gray-500', $html);   // no boxed buttons
        $this->assertStringNotContainsString('wire:click="confirmDelete(', $html);              // Inactive in Delete's place
        $this->assertStringContainsString('wire:click="toggleCardStatus(' . $s['ashaCard'] . ')" title="Make inactive"', $html);
        $this->assertStringContainsString('wire:click="issueOne(' . $s['chand'] . ')"', $html);
    }

    public function test_view_inactive_and_issue(): void
    {
        $s    = $this->school();
        $page = $this->page($s);

        // View: the card's PDF in a panel, as TC shows one.
        $html = $page->call('viewCard', $s['ashaCard'])->html();
        $this->assertStringContainsString('/admit-card/' . $s['ashaCard'] . '/pdf#toolbar=0&amp;navpanes=0&amp;view=FitH', $html);
        $this->assertStringContainsString('Download PDF', $html);
        $this->assertNull($page->call('closeView')->get('viewCardId'));

        // Inactive keeps the card; a second click brings it back.
        $page->call('toggleCardStatus', $s['ashaCard']);
        $this->assertSame('inactive', DB::table('admit_cards')->where('id', $s['ashaCard'])->value('status'));
        $html = $page->html();
        $this->assertStringContainsString('<p class="text-sm text-red-600">Inactive</p>', $html);
        $this->assertStringContainsString('title="Make active"', $html);
        $page->call('toggleCardStatus', $s['ashaCard']);
        $this->assertSame('active', DB::table('admit_cards')->where('id', $s['ashaCard'])->value('status'));

        // Not issued: listed on their own, issued from the row (by hand = no criteria).
        $html = $page->set('statusFilter', 'not_issued')->html();
        $this->assertStringContainsString('Chand', $html);
        $this->assertStringNotContainsString('Asha', $html);
        $page->call('issueOne', $s['chand']);
        $card = DB::table('admit_cards')->where('student_detail_id', $s['chand'])->first();
        $this->assertSame(['active', 'none'], [$card->status, $card->issue_criteria]);
    }

    public function test_bulk_issue_keeps_the_criteria(): void
    {
        $s = $this->school();
        DB::table('admit_cards')->where('student_detail_id', $s['bilal'])->delete();

        // Attendance at 50% or more: Bilal (50%) and Chand (no days marked) qualify.
        Livewire::test(AdmitCard::class)->call('openGenerateModal')
            ->set('genExam', (string) $s['exam'])->set('genStandard', (string) $s['std'])
            ->set('genCriteria', 'attendance')->set('genPercentage', 50)->call('generateAdmitCards');

        $this->assertSame('attendance', DB::table('admit_cards')->where('student_detail_id', $s['bilal'])->value('issue_criteria'));
        $this->assertSame('attendance', DB::table('admit_cards')->where('student_detail_id', $s['chand'])->value('issue_criteria'));
        $this->assertSame('fee', DB::table('admit_cards')->where('student_detail_id', $s['asha'])->value('issue_criteria'));   // already had one
    }

    public function test_accounts_copy_has_the_same_list(): void
    {
        $s    = $this->school();
        $html = $this->page($s, AccountsAdmitCard::class)->html();

        $this->assertStringContainsString('>Issued on</th>', $html);
        $this->assertMatchesRegularExpression('/Fee 25% \/ Attendance 50%/', $html);
        $this->assertStringNotContainsString('>Roll</th>', $html);
    }
}

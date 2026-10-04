<?php

namespace Tests\Feature;

use App\Livewire\Admin\Arrangement;
use App\Models\Admin\TeacherArrangement;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Arrangement: the day's absent teachers in the Students list's table (periods,
 * covered, covered by, the status dot and the pencil); the pencil opens that
 * teacher's periods in the Mark Attendance panel's slide-in, a plain row each,
 * where a free substitute is assigned, edited or removed as before.
 */
class ArrangementPanelTest extends TestCase
{
    private int $org = 9;
    private array $t = [];

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-17 10:00:00');   // a Thursday
        config(['app.key' => 'base64:' . base64_encode(str_repeat('a', 32))]);
        // The substitute's push is not what is tried here.
        TeacherArrangement::flushEventListeners();

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('username')->nullable();
            $t->string('email')->nullable();
            $t->string('image')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('organization_id');
            $t->timestamps();
        });
        Schema::create('teacher_attendances', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('teacher_detail_id');
            $t->unsignedBigInteger('organization_id');
            $t->date('attendance_date');
            $t->integer('status');
            $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('name');
            $t->integer('order')->default(0);
            $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->unsignedBigInteger('standard_id');
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('subjects', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('teacher_time_tables', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('teacher_detail_id');
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable();
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->integer('day_of_week');
            $t->time('start_time');
            $t->time('end_time');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('teacher_arrangements', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('original_teacher_id');
            $t->unsignedBigInteger('substitute_teacher_id')->nullable();
            $t->unsignedBigInteger('teacher_time_table_id');
            $t->date('date');
            $t->string('reason')->nullable();
            $t->unsignedBigInteger('arranged_by')->nullable();
            $t->timestamps();
        });

        $teacher = function (string $name) {
            $uid = DB::table('users')->insertGetId(['name' => $name, 'username' => strtolower($name) . '01', 'organization_id' => $this->org]);
            return DB::table('teacher_details')->insertGetId(['user_id' => $uid, 'organization_id' => $this->org]);
        };
        $this->t = ['asha' => $teacher('Asha'), 'bina' => $teacher('Bina'), 'chetan' => $teacher('Chetan')];
        DB::table('teacher_attendances')->insert(['teacher_detail_id' => $this->t['asha'], 'organization_id' => $this->org, 'attendance_date' => '2026-09-17', 'status' => 0]);

        $std = DB::table('standards')->insertGetId(['organization_id' => $this->org, 'name' => '5']);
        $sec = DB::table('sections')->insertGetId(['organization_id' => $this->org, 'standard_id' => $std, 'name' => 'A']);
        $sub = DB::table('subjects')->insertGetId(['organization_id' => $this->org, 'name' => 'Hindi']);
        $slot = fn ($teacher, $from, $to) => DB::table('teacher_time_tables')->insertGetId([
            'organization_id' => $this->org, 'teacher_detail_id' => $teacher, 'standard_id' => $std, 'section_id' => $sec,
            'subject_id' => $sub, 'day_of_week' => 4, 'start_time' => $from, 'end_time' => $to,
        ]);
        $this->t['p1'] = $slot($this->t['asha'], '08:00:00', '08:40:00');
        $this->t['p2'] = $slot($this->t['asha'], '08:40:00', '09:20:00');
        // Bina teaches in period 1 herself, so she is free only for period 2.
        $slot($this->t['bina'], '08:00:00', '08:40:00');

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 700;
        $admin->organization_id = $this->org;
        $this->actingAs($admin);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_the_absent_teachers_are_a_students_style_list(): void
    {
        $html = Livewire::test(Arrangement::class)
            ->assertSee('Asha')
            ->assertSee('asha01')
            ->assertSee('2 periods')
            ->assertSee('0 of 2')
            ->assertSee('2 pending')
            ->assertSeeHtml('wire:click="openArrange(' . $this->t['asha'] . ')"')
            ->assertDontSee('Bina')            // present
            ->html();

        $this->assertStringContainsString('<th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Covered By</th>', $html);
        $this->assertStringNotContainsString('Substitute…', $html);   // periods only in the slide-in
    }

    public function test_the_slide_in_lists_the_periods_and_assigns_a_free_substitute(): void
    {
        $page = Livewire::test(Arrangement::class)
            ->call('openArrange', $this->t['asha'])
            ->assertSet('arrangeTeacherId', $this->t['asha'])
            ->assertSee('Arrange · Asha')
            ->assertSee('0 of 2 covered')
            ->assertSeeHtml('wire:key="arr-slot-' . $this->t['p1'] . '"')
            ->assertSeeHtml('wire:key="arr-slot-' . $this->t['p2'] . '"')
            ->assertSee('P1')
            ->assertSee('P2')
            ->assertViewHas('slotAvailability', function ($a) {
                $names = fn ($id) => $a[$id]->map(fn ($t) => $t->user->name)->all();
                // Bina teaches in period 1; Chetan is free in both.
                return $names($this->t['p1']) === ['Chetan'] && $names($this->t['p2']) === ['Bina', 'Chetan'];
            });

        $page->set("slotSubstitutes.{$this->t['p2']}", (string) $this->t['bina'])
            ->set("slotReasons.{$this->t['p2']}", 'Sick leave')
            ->call('assignSlot', $this->t['p2'])
            ->assertSee('1 of 2 covered')
            ->assertSee('Sick leave');

        $arr = TeacherArrangement::first();
        $this->assertSame([$this->t['bina'], $this->t['p2'], 'Sick leave'], [(int) $arr->substitute_teacher_id, (int) $arr->teacher_time_table_id, $arr->reason]);

        // Edit swaps the substitute; Remove asks first.
        $page->call('editArrangement', $this->t['p2'])
            ->set("slotSubstitutes.{$this->t['p2']}", (string) $this->t['chetan'])
            ->call('updateSlot', $this->t['p2']);
        $this->assertSame($this->t['chetan'], (int) $arr->fresh()->substitute_teacher_id);

        $page->call('deleteArrangement', $arr->id)->assertSet('showDeleteConfirm', true)->call('confirmDelete');
        $this->assertSame(0, TeacherArrangement::count());

        // Closing it, or another date, puts the list back.
        $page->call('closeArrange')->assertSet('arrangeTeacherId', null);
        $page->call('openArrange', $this->t['asha'])->set('date', '2026-09-18')->assertSet('arrangeTeacherId', null);
    }

    public function test_clear_sits_right_after_the_filters_and_brings_back_today(): void
    {
        $page = Livewire::test(Arrangement::class)->assertDontSeeHtml('wire:click="clearFilters"');

        $html = $page->set('date', '2026-09-18')->assertSeeHtml('wire:click="clearFilters"')->html();
        // After the class box, before the "Showing slots for" line.
        $this->assertLessThan(strpos($html, 'Showing slots for'), strpos($html, 'wire:click="clearFilters"'));
        $this->assertGreaterThan(strpos($html, 'wire:model.live="filterClass"'), strpos($html, 'wire:click="clearFilters"'));

        $page->call('clearFilters')->assertSet('date', '2026-09-17')->assertSet('filterClass', '')
            ->assertDontSeeHtml('wire:click="clearFilters"');
    }
}

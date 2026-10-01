<?php

namespace Tests\Feature;

use App\Livewire\Admin\IdCard;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The panel's ID Card list: the Employees tab leaves teachers out (management,
 * employees and drivers stay) and shows mobile and email; the Teachers tab
 * shows the username under the name and the mobile; the Students tab has the
 * class, with the section under it, after the card number. Status is a dot
 * before the actions, the list has no download button and no page-size choice,
 * and View shows both faces with Download / Print.
 */
class IdCardPanelTest extends TestCase
{
    private int $org = 7;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:' . base64_encode(str_repeat('k', 32))]);

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('username')->nullable();
            $t->string('role')->nullable(); $t->string('image')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('logo')->nullable(); $t->string('address')->nullable();
            $t->string('email')->nullable(); $t->string('mobile_number')->nullable(); $t->timestamps();
        });
        Schema::create('school_infos', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name');
            $t->integer('order')->default(0); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('standard_id'); $t->string('name'); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->string('full_name')->nullable(); $t->string('admission_no')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->timestamps();
        });
        // A student's card looks up their bus route for its back.
        Schema::create('transportations', function (Blueprint $t) {
            $t->id(); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('transportation_students', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('student_detail_id'); $t->unsignedBigInteger('transportation_id'); $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->string('employee_id')->nullable(); $t->string('phone')->nullable(); $t->timestamps();
        });
        Schema::create('admin_employees', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->string('type');
            $t->unsignedBigInteger('teacher_detail_id')->nullable(); $t->unsignedBigInteger('driver_detail_id')->nullable();
            $t->string('email')->nullable(); $t->string('mobile')->nullable(); $t->string('designation')->nullable();
            $t->string('photo')->nullable(); $t->string('address')->nullable(); $t->string('joining_date')->nullable();
            $t->timestamps();
        });
        foreach (['student_id_cards' => 'student_detail_id', 'teacher_id_cards' => 'teacher_detail_id', 'employee_id_cards' => 'admin_employee_id'] as $table => $holder) {
            Schema::create($table, function (Blueprint $t) use ($holder) {
                $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->default(0);
                $t->unsignedBigInteger($holder); $t->string('card_number'); $t->date('issue_date')->nullable();
                $t->date('expiry_date')->nullable(); $t->string('status')->default('active'); $t->text('qr_code')->nullable();
                $t->timestamps();
            });
        }

        DB::table('organizations')->insert(['id' => $this->org, 'name' => 'Delhi Public School']);
        $this->actingAs(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]));
    }

    private function card(string $table, string $holder, int $id, string $number, string $status = 'active'): int
    {
        return DB::table($table)->insertGetId([
            'organization_id' => $this->org, $holder => $id, 'card_number' => $number,
            'issue_date' => '2026-10-01', 'expiry_date' => '2027-03-31', 'status' => $status, 'qr_code' => 'eA==',
        ]);
    }

    private function employee(string $name, string $type, array $more = []): int
    {
        return DB::table('admin_employees')->insertGetId(['organization_id' => $this->org, 'name' => $name, 'type' => $type] + $more);
    }

    /** The page without Livewire's block markers, cut down to its table. */
    private function table(string $html): string
    {
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        return substr($html, strpos($html, '<table'), strpos($html, '</table>') - strpos($html, '<table'));
    }

    public function test_the_employees_tab_leaves_teachers_out_and_shows_mobile_and_email(): void
    {
        $this->card('employee_id_cards', 'admin_employee_id', $this->employee('Anita Verma', 'teacher'), 'IDEMP-T');
        $this->card('employee_id_cards', 'admin_employee_id',
            $this->employee('Mohan Lal', 'management', ['mobile' => '9811111111', 'email' => 'mohan@dps.test', 'designation' => 'Principal']), 'IDEMP-M');
        $this->card('employee_id_cards', 'admin_employee_id', $this->employee('Sita Devi', 'employee', ['mobile' => '9822222222']), 'IDEMP-E');
        $this->employee('Ravi Kumar', 'driver');          // no card yet
        $this->employee('Pooja Singh', 'teacher');        // a teacher without one: not this tab's either

        $page  = Livewire::test(IdCard::class)->call('switchCardType', 'employee');
        $table = $this->table($page->html());

        $this->assertStringContainsString('Mohan Lal', $table);
        $this->assertStringContainsString('Sita Devi', $table);
        $this->assertStringNotContainsString('Anita Verma', $table);
        $this->assertStringContainsString('9811111111', $table);
        $this->assertStringContainsString('mohan@dps.test', $table);
        $this->assertMatchesRegularExpression('/Employee<\/th>\s*<th[^>]*>Mobile<\/th>\s*<th[^>]*>Email<\/th>\s*<th[^>]*>Card No\.<\/th>\s*<th[^>]*>Expiry<\/th>\s*<th[^>]*>Actions<\/th>/', $table);

        // The header counts the same people: three of them, two with a card.
        $this->assertSame(['total' => 3, 'issued' => 2, 'remaining' => 1], $page->instance()->analytics);

        // Not issued lists the driver, and not the teacher.
        $table = $this->table($page->set('issueFilter', 'not_issued')->html());
        $this->assertStringContainsString('Ravi Kumar', $table);
        $this->assertStringNotContainsString('Pooja Singh', $table);
        $this->assertStringContainsString('Not issued', $table);
    }

    public function test_the_teachers_tab_shows_the_username_under_the_name_and_the_mobile(): void
    {
        $user = User::forceCreate(['name' => 'Anita Verma', 'username' => 'anita.verma', 'role' => 'teacher', 'organization_id' => $this->org]);
        $tid  = DB::table('teacher_details')->insertGetId(['organization_id' => $this->org, 'user_id' => $user->id, 'employee_id' => 'T-014', 'phone' => '9833333333']);
        $this->card('teacher_id_cards', 'teacher_detail_id', $tid, 'IDTCH-1');

        $table = $this->table(Livewire::test(IdCard::class)->call('switchCardType', 'teacher')->html());

        $this->assertMatchesRegularExpression('/Anita Verma<\/p>\s*<p class="text-xs text-gray-400 truncate">anita\.verma<\/p>/', $table);
        $this->assertStringNotContainsString('T-014', $table);
        $this->assertMatchesRegularExpression('/Teacher<\/th>\s*<th[^>]*>Mobile<\/th>\s*<th[^>]*>Card No\.<\/th>\s*<th[^>]*>Expiry<\/th>\s*<th[^>]*>Actions<\/th>/', $table);
        $this->assertStringContainsString('9833333333', $table);
    }

    public function test_the_students_tab_has_the_class_after_the_card_number_and_a_plain_status(): void
    {
        $std = DB::table('standards')->insertGetId(['organization_id' => $this->org, 'name' => 'Class 5']);
        $sec = DB::table('sections')->insertGetId(['standard_id' => $std, 'name' => 'Section B']);
        $one = DB::table('student_details')->insertGetId(['organization_id' => $this->org, 'full_name' => 'Aarav Sharma', 'admission_no' => 'A1', 'standard_id' => $std, 'section_id' => $sec]);
        $two = DB::table('student_details')->insertGetId(['organization_id' => $this->org, 'full_name' => 'Bhavna Gupta', 'admission_no' => 'A2', 'standard_id' => $std, 'section_id' => $sec]);
        $cardId = $this->card('student_id_cards', 'student_detail_id', $one, 'IDSTU-1');
        $this->card('student_id_cards', 'student_detail_id', $two, 'IDSTU-2', 'inactive');

        $page  = Livewire::test(IdCard::class)->set('standardFilter', $std);
        $html  = $page->html();
        $table = $this->table($html);

        $this->assertMatchesRegularExpression('/Student<\/th>\s*<th[^>]*>Card No\.<\/th>\s*<th[^>]*>Class<\/th>\s*<th[^>]*>Expiry<\/th>\s*<th[^>]*>Actions<\/th>/', $table);
        $this->assertMatchesRegularExpression('/IDSTU-1<\/td>\s*<td class="px-4 py-3">\s*<span[^>]*>Class 5<\/span>\s*<span class="block text-xs text-gray-400">Section B<\/span>/', $table);

        // Status: the Students list's dot, not a column of pills.
        $this->assertStringNotContainsString('>Status</th>', $table);
        $this->assertSame(1, preg_match_all('/bg-green-500"\s+title="Active"/', $table));
        $this->assertSame(1, preg_match_all('/bg-red-500"\s+title="Inactive"/', $table));

        // View / Edit / Delete only: Download / Print is on View, and the page size is fixed.
        $this->assertStringNotContainsString('/print', $table);
        $this->assertStringNotContainsString('wire:model.live="perPage"', $html);
        $this->assertSame(100, $page->get('perPage'));
        $this->assertSame(2, substr_count($table, 'wire:click="showCard('));

        // View: both faces in one row, with Download / Print as before.
        $view = $page->call('showCard', $cardId)->html();
        $this->assertStringContainsString('class="idc-view ', $view);
        $this->assertSame(2, substr_count($view, 'class="idc-card"'));
        $this->assertStringContainsString('/id-card/student/' . $cardId . '/print', $view);
        $this->assertStringContainsString('Download / Print', $view);
    }
}

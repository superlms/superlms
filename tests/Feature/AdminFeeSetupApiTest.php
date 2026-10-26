<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\AdminFeeSetupController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The admin app's Fee sections beyond collecting run the panel's own
 * components: Fee Structure (and Add Dues, a route's fee), Fee Cycle,
 * Concession, Penalties and Accounts user save exactly what the panel saves,
 * and say what stopped them the way the panel's toast does.
 */
class AdminFeeSetupApiTest extends TestCase
{
    private int $org = 21;
    private int $std;
    private int $secA;
    private int $secB;
    private int $student;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('password')->nullable(); $t->string('image')->nullable();
            $t->string('role')->nullable(); $t->string('mobile_number')->nullable(); $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('school_users', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('organization_id')->nullable(); $t->string('employee_id')->nullable();
            $t->string('designation')->nullable(); $t->string('department')->nullable(); $t->string('phone')->nullable(); $t->string('address')->nullable();
            $t->string('profile_picture')->nullable(); $t->boolean('is_active')->default(true); $t->string('image')->nullable();
            $t->string('alternate_mobile_number')->nullable(); $t->timestamps();
        });
        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('school_code')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->boolean('is_active')->default(true);
            $t->integer('order')->nullable(); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id'); $t->string('name');
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('section_id');
            $t->string('full_name')->nullable(); $t->string('father_name')->nullable(); $t->string('mother_name')->nullable();
            $t->string('admission_no')->nullable(); $t->string('roll_no')->nullable(); $t->string('phone')->nullable();
            $t->string('image')->nullable(); $t->timestamps();
        });
        Schema::create('fee_structures', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('section_id')->nullable();
            $t->unsignedBigInteger('student_detail_id')->nullable();
            $t->string('fee_type'); $t->string('fee_name')->nullable(); $t->decimal('amount', 12, 2); $t->boolean('is_active')->default(true);
            $t->string('academic_year')->nullable(); $t->timestamps();
        });
        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id');
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->string('fee_type'); $t->decimal('amount', 12, 2); $t->decimal('waiver_amount', 12, 2)->nullable(); $t->string('waiver_reason')->nullable();
            $t->decimal('penalty_amount', 12, 2)->nullable(); $t->string('payment_mode')->nullable(); $t->date('payment_date')->nullable();
            $t->text('remark')->nullable(); $t->string('submitted_by')->nullable(); $t->string('receipt_number')->nullable(); $t->timestamps();
        });
        Schema::create('fee_concessions', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id');
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable();
            $t->string('fee_type'); $t->string('concession_type'); $t->decimal('value', 12, 2); $t->string('reason')->nullable();
            $t->string('academic_year')->nullable(); $t->boolean('is_penalty')->default(false); $t->unsignedBigInteger('fee_cycle_id')->nullable();
            $t->unsignedBigInteger('created_by')->nullable(); $t->string('collected_by')->nullable(); $t->string('payment_mode')->nullable(); $t->timestamps();
        });
        Schema::create('fee_cycles', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('fee_type'); $t->integer('payment_serial')->nullable();
            $t->date('start_date')->nullable(); $t->date('end_date')->nullable(); $t->date('due_date')->nullable();
            $t->decimal('penalty_per_day', 10, 2)->default(0); $t->decimal('fee_percent', 6, 2)->default(0); $t->decimal('amount', 12, 2)->default(0);
            $t->boolean('is_token')->default(false); $t->string('academic_year')->nullable(); $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('transportations', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('route_name'); $t->string('vehicle_type')->nullable();
            $t->decimal('monthly_fee', 12, 2)->default(0); $t->string('pickup_time')->nullable();
            $t->boolean('is_active')->default(true); $t->unsignedBigInteger('driver_detail_id')->nullable(); $t->timestamps();
        });
        Schema::create('transportation_students', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('transportation_id'); $t->unsignedBigInteger('student_detail_id');
            $t->text('billable_months')->nullable(); $t->timestamps();
        });
        Schema::create('transport_fee_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('transportation_id')->nullable(); $t->unsignedBigInteger('student_detail_id');
            $t->decimal('amount', 12, 2); $t->string('payment_mode')->nullable(); $t->date('payment_date')->nullable(); $t->string('academic_year')->nullable();
            $t->text('remark')->nullable(); $t->unsignedBigInteger('submitted_by')->nullable(); $t->string('receipt_number')->nullable(); $t->timestamps();
        });

        DB::table('organizations')->insert(['id' => $this->org, 'name' => 'DPS', 'school_code' => 'DPS']);
        $this->std  = DB::table('standards')->insertGetId(['organization_id' => $this->org, 'name' => 'CLASS 5']);
        $this->secA = DB::table('sections')->insertGetId(['organization_id' => $this->org, 'standard_id' => $this->std, 'name' => 'A']);
        $this->secB = DB::table('sections')->insertGetId(['organization_id' => $this->org, 'standard_id' => $this->std, 'name' => 'B']);
        $u = DB::table('users')->insertGetId(['name' => 'Aarav', 'role' => 'user', 'organization_id' => $this->org]);
        $this->student = DB::table('student_details')->insertGetId([
            'user_id' => $u, 'organization_id' => $this->org, 'standard_id' => $this->std, 'section_id' => $this->secA,
            'full_name' => 'Aarav', 'admission_no' => '26DPS1',
        ]);

        Auth::setUser(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]));
    }

    private function api(): AdminFeeSetupController
    {
        return app(AdminFeeSetupController::class);
    }

    private function data($response): array
    {
        return $response->getData(true)['data'] ?? [];
    }

    public function test_a_fee_structure_is_added_per_section_listed_edited_and_deleted(): void
    {
        $add = $this->api()->addStructure(new Request([
            'standard_id'   => $this->std,
            'section_ids'   => [$this->secA, $this->secB],
            'academic_year' => '2026-27',
            'rows'          => [['name' => 'Tuition', 'amount' => '1000'], ['name' => 'Lab', 'amount' => '250']],
        ]));
        $this->assertSame(200, $add->status(), json_encode($add->getData(true)));
        $this->assertSame('2 fee head(s) added for 2 section(s).', $add->getData(true)['message']);
        $this->assertSame(4, DB::table('fee_structures')->count());

        $list = $this->data($this->api()->structure(new Request(['standard_id' => $this->std])));
        $this->assertCount(2, $list['groups']);
        $this->assertSame(1250.0, (float) $list['groups'][0]['total']);
        $this->assertSame(['Tuition', 'Lab'], array_column($list['groups'][0]['rows'], 'fee_name'));

        // Editing a group replaces its heads wholesale.
        $edit = $this->api()->editStructure(new Request([
            'standard_id' => $this->std, 'section_id' => $this->secA, 'academic_year' => '2026-27',
            'rows'        => [['name' => 'Tuition', 'amount' => '1200']],
        ]));
        $this->assertSame(200, $edit->status(), json_encode($edit->getData(true)));
        $this->assertSame(1, DB::table('fee_structures')->where('section_id', $this->secA)->count());
        $this->assertSame(1200.0, (float) DB::table('fee_structures')->where('section_id', $this->secA)->value('amount'));

        $del = $this->api()->deleteStructure(new Request(['standard_id' => $this->std, 'section_id' => $this->secB]));
        $this->assertSame(200, $del->status());
        $this->assertSame(0, DB::table('fee_structures')->where('section_id', $this->secB)->count());
    }

    public function test_a_fee_structure_says_which_field_is_wrong_the_apps_way(): void
    {
        $res = $this->api()->addStructure(new Request([
            'standard_id' => $this->std, 'academic_year' => '2026-27',
            'rows'        => [['name' => '', 'amount' => '100']],
        ]));
        $this->assertSame(422, $res->status());
        $this->assertArrayHasKey('rows.0.name', $res->getData(true)['errors']);
    }

    public function test_dues_are_read_and_saved_for_the_class(): void
    {
        $save = $this->api()->saveDues(new Request([
            'standard_id' => $this->std,
            'amounts'     => [$this->student => '1500'],
            'extras'      => [$this->student => [['name' => '2024-25 Fee', 'amount' => '700']]],
        ]));
        $this->assertSame(200, $save->status(), json_encode($save->getData(true)));

        $d = $this->data($this->api()->dues(new Request(['standard_id' => $this->std])));
        $this->assertSame('1500', $d['students'][0]['amount']);
        $this->assertSame('2024-25 Fee', $d['students'][0]['extras'][0]['name']);
        $this->assertCount(2, $d['sections']);

        // Saving again without the student keeps what is saved for them.
        $this->api()->saveDues(new Request(['standard_id' => $this->std, 'amounts' => []]));
        $this->assertSame(2, DB::table('fee_structures')->whereNotNull('student_detail_id')->count());
    }

    public function test_a_route_fee_is_edited_and_a_route_with_riders_is_not_deleted(): void
    {
        $route = DB::table('transportations')->insertGetId(['organization_id' => $this->org, 'route_name' => 'North', 'monthly_fee' => 800]);

        $view = $this->data($this->api()->structure(new Request(['route_id' => $route])));
        $this->assertSame(8800.0, (float) $view['route']['annual']); // June is free
        $this->assertSame(11, $view['route']['billable_months']);

        $ok = $this->api()->saveRoute(new Request(['name' => 'North', 'monthly_fee' => '900', 'is_active' => true]), $route);
        $this->assertSame(200, $ok->status(), json_encode($ok->getData(true)));
        $this->assertSame(900.0, (float) DB::table('transportations')->value('monthly_fee'));

        DB::table('transportation_students')->insert(['organization_id' => $this->org, 'transportation_id' => $route, 'student_detail_id' => $this->student]);
        $no = $this->api()->deleteRoute($route);
        $this->assertSame(422, $no->status());
        $this->assertStringContainsString('Route still has students', $no->getData(true)['message']);
    }

    public function test_a_monthly_cycle_is_made_listed_and_rebalanced(): void
    {
        $add = $this->api()->addCycles(new Request(['mode' => 'monthly', 'monthly_due_day' => 10, 'penalty_per_day' => '5']));
        $this->assertSame(200, $add->status(), json_encode($add->getData(true)));
        $this->assertSame('12 monthly installments created!', $add->getData(true)['message']);

        $list = $this->data($this->api()->cycles());
        $this->assertSame('monthly', $list['groups'][0]['kind']);
        $this->assertSame(12, $list['groups'][0]['count']);
        $this->assertEqualsWithDelta(100, $list['groups'][0]['percent'], 0.01);

        // One installment's % changed: the others give up the difference.
        $first = $list['groups'][0]['rows'][0];
        $save  = $this->api()->saveCycle(new Request([
            'serial' => 1, 'due_date' => $first['due_date'], 'start_date' => $first['start_date'], 'end_date' => $first['end_date'],
            'fee_percent' => '19.33', 'penalty_per_day' => '5',
        ]), $first['id']);
        $this->assertSame(200, $save->status(), json_encode($save->getData(true)));
        $this->assertEqualsWithDelta(100, (float) DB::table('fee_cycles')->where('is_token', false)->sum('fee_percent'), 0.05);

        $bulk = $this->data($this->api()->cycleBulk(new Request(['fee_type' => 'academic', 'year' => $first['year']])));
        $this->assertCount(12, $bulk['rows']);
        $this->assertSame('monthly', $bulk['kind']);
    }

    public function test_a_custom_cycle_row_without_its_span_is_refused_with_the_panels_words(): void
    {
        $res = $this->api()->addCycles(new Request([
            'mode' => 'custom',
            'rows' => [['serial' => 1, 'due_date' => '2026-05-10', 'fee_percent' => '100', 'penalty_per_day' => '0']],
        ]));
        $this->assertSame(422, $res->status());
        $this->assertSame('Row 1: pick both a start and an end date.', $res->getData(true)['message']);
    }

    public function test_a_concession_is_added_listed_and_edited(): void
    {
        $add = $this->api()->saveConcession(new Request([
            'student_id' => $this->student, 'type' => 'percent', 'value' => '10', 'fee_type' => 'academic', 'reason' => 'Sibling', 'year' => '2026-27',
        ]));
        $this->assertSame(200, $add->status(), json_encode($add->getData(true)));

        $list = $this->data($this->api()->concessions(new Request(['standard_id' => $this->std])));
        $this->assertCount(1, $list['concessions']);
        $this->assertSame('Academic', $list['concessions'][0]['applies_to']);
        $id = $list['concessions'][0]['id'];

        $edit = $this->api()->saveConcession(new Request([
            'student_id' => $this->student, 'type' => 'amount', 'value' => '500', 'fee_type' => 'academic', 'year' => '2026-27',
        ]), $id);
        $this->assertSame(200, $edit->status());
        $this->assertSame('amount', DB::table('fee_concessions')->value('concession_type'));

        // Last Year Dues need some dues to come off.
        $dues = $this->api()->saveConcession(new Request([
            'student_id' => $this->student, 'type' => 'amount', 'value' => '100', 'fee_type' => 'dues', 'year' => '2026-27',
        ]));
        $this->assertSame(422, $dues->status());
        $this->assertSame('This student has no Last Year Dues.', $dues->getData(true)['errors']['fee_type'][0]);
    }

    public function test_penalties_list_the_class(): void
    {
        DB::table('fee_structures')->insert(['organization_id' => $this->org, 'standard_id' => $this->std, 'fee_type' => 'academic', 'fee_name' => 'Tuition', 'amount' => 1200]);
        $d = $this->data($this->api()->penaltyClass(new Request(['standard_id' => $this->std])));
        $this->assertSame(['Aarav'], array_column($d['students'], 'name'));
        $this->assertSame(0.0, (float) $d['total']);
    }

    public function test_an_accounts_login_is_added_switched_off_and_deleted(): void
    {
        $add = $this->api()->saveAccountUser(new Request([
            'name' => 'Cashier', 'email' => 'cash@dps.test', 'password' => 'Secret123', 'mobile_number' => '9876543210',
        ]));
        $this->assertSame(200, $add->status(), json_encode($add->getData(true)));
        $id = (int) DB::table('users')->where('role', 'accounts')->value('id');

        $list = $this->data($this->api()->accountUsers(new Request()));
        $this->assertSame(1, $list['stats']['total']);
        $this->assertSame('Cashier', $list['users'][0]['name']);

        $off = $this->api()->toggleAccountUser($id);
        $this->assertFalse($this->data($off)['is_active']);

        $short = $this->api()->saveAccountUser(new Request(['name' => 'X', 'email' => 'x@dps.test', 'password' => 'short']));
        $this->assertSame(422, $short->status());
        $this->assertArrayHasKey('password', $short->getData(true)['errors']);

        $this->assertSame(200, $this->api()->deleteAccountUser($id)->status());
        $this->assertSame(0, DB::table('users')->where('role', 'accounts')->count());
    }
}

<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\AdminFeeController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The admin app's Fees reads and collects through the panel's own Fee
 * component: a student's ledger net of concession, and a payment never more
 * than is due for its fee type.
 */
class AdminFeeApiTest extends TestCase
{
    private int $org = 11;
    private int $student;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('image')->nullable(); $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
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
            $t->string('fee_type'); $t->string('concession_type'); $t->decimal('value', 12, 2); $t->string('reason')->nullable();
            $t->string('academic_year')->nullable(); $t->boolean('is_penalty')->default(false); $t->unsignedBigInteger('fee_cycle_id')->nullable(); $t->timestamps();
        });
        Schema::create('fee_cycles', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('fee_type'); $t->boolean('is_active')->default(true);
            $t->string('academic_year')->nullable(); $t->integer('payment_serial')->nullable(); $t->timestamps();
        });
        Schema::create('transportations', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('route_name'); $t->decimal('monthly_fee', 12, 2)->default(0);
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

        Schema::create('fee_payment_requests', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('student_detail_id'); $t->unsignedBigInteger('user_id');
            $t->string('fee_type', 20); $t->decimal('amount', 10, 2); $t->string('utr', 40)->nullable(); $t->string('screenshot_path')->nullable();
            $t->date('paid_on'); $t->string('note', 500)->nullable(); $t->json('meta')->nullable(); $t->string('status', 20)->default('pending');
            $t->decimal('approved_amount', 10, 2)->nullable(); $t->unsignedBigInteger('reviewed_by')->nullable(); $t->timestamp('reviewed_at')->nullable();
            $t->string('review_note', 500)->nullable(); $t->unsignedBigInteger('fee_payment_id')->nullable();
            $t->unsignedBigInteger('transport_fee_payment_id')->nullable(); $t->timestamps();
        });

        // A decision tells the student — never a real phone from a test.
        $this->app->instance(\App\Services\FirebaseNotificationService::class, new class {
            public array $sent = [];
            public function notifyUser(...$args) { $this->sent[] = $args; }
        });

        DB::table('organizations')->insert(['id' => $this->org, 'name' => 'DPS', 'school_code' => 'DPS']);
        $std = DB::table('standards')->insertGetId(['organization_id' => $this->org, 'name' => 'CLASS 5']);
        $sec = DB::table('sections')->insertGetId(['organization_id' => $this->org, 'standard_id' => $std, 'name' => 'A']);
        $u = DB::table('users')->insertGetId(['name' => 'Aarav', 'role' => 'user', 'organization_id' => $this->org]);
        $this->student = DB::table('student_details')->insertGetId([
            'user_id' => $u, 'organization_id' => $this->org, 'standard_id' => $std, 'section_id' => $sec,
            'full_name' => 'Aarav', 'admission_no' => '26DPS1',
        ]);
        DB::table('fee_structures')->insert(['organization_id' => $this->org, 'standard_id' => $std, 'fee_type' => 'academic', 'fee_name' => 'Tuition', 'amount' => 10000]);
        DB::table('fee_concessions')->insert(['organization_id' => $this->org, 'student_detail_id' => $this->student,
            'fee_type' => 'academic', 'concession_type' => 'percent', 'value' => 10, 'reason' => 'Sibling', 'created_at' => now()]);

        Auth::setUser(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => $this->org]));
    }

    private function api(): AdminFeeController
    {
        return app(AdminFeeController::class);
    }

    public function test_the_ledger_is_net_of_concession_and_caps_what_may_be_collected(): void
    {
        $d = $this->api()->ledger($this->student)->getData(true)['data'];

        $this->assertSame(10000.0, (float) $d['academic']['gross']);
        $this->assertSame(1000.0, (float) $d['academic']['concession']);
        $this->assertSame(9000.0, (float) $d['academic']['net']);
        $this->assertSame(9000.0, (float) $d['caps']['academic']);
        $this->assertSame(0.0, (float) $d['caps']['transport']);
        $this->assertFalse($d['hasTransport']);
    }

    public function test_a_payment_is_taken_up_to_what_is_due_and_no_further(): void
    {
        $body = ['fee_type' => 'academic', 'payment_mode' => 'cash', 'date' => now()->toDateString(), 'submitted_by' => 'Head'];

        $ok = $this->api()->collect(new Request($body + ['amount' => 4000]), $this->student);
        $this->assertSame(200, $ok->status());
        $this->assertSame(1, DB::table('fee_payments')->count());

        // 5,000 left; 6,000 is refused.
        $over = $this->api()->collect(new Request($body + ['amount' => 6000]), $this->student);
        $this->assertSame(422, $over->status());

        // No bus, so nothing to collect for transport.
        $bus = $this->api()->collect(new Request(['fee_type' => 'transport'] + $body + ['amount' => 100]), $this->student);
        $this->assertSame(422, $bus->status());

        $d = $this->api()->ledger($this->student)->getData(true)['data'];
        $this->assertSame(5000.0, (float) $d['caps']['academic']);
        $this->assertCount(1, $d['payments']);
    }

    public function test_the_class_list_finds_the_student(): void
    {
        $std = DB::table('standards')->value('id');
        $d = $this->api()->students(new Request(['standard_id' => $std]))->getData(true)['data'];
        $this->assertSame(['Aarav'], array_column($d['students'], 'name'));

        $byName = $this->api()->students(new Request(['search' => 'aar']))->getData(true)['data'];
        $this->assertCount(1, $byName['students']);
    }

    public function test_the_class_list_carries_what_each_student_still_owes(): void
    {
        $std = DB::table('standards')->value('id');
        DB::table('fee_payments')->insert(['organization_id' => $this->org, 'student_detail_id' => $this->student, 'standard_id' => $std,
            'fee_type' => 'academic', 'amount' => 2500, 'payment_mode' => 'cash', 'payment_date' => now()->toDateString(), 'receipt_number' => 'R1']);

        $d = $this->api()->students(new Request(['standard_id' => $std]))->getData(true)['data'];

        // View Fee's By Class figures: the class's fee heads, before concession.
        $this->assertSame(10000.0, (float) $d['students'][0]['fee']['total']);
        $this->assertSame(2500.0, (float) $d['students'][0]['fee']['collected']);
        $this->assertSame(7500.0, (float) $d['students'][0]['fee']['pending']);

        // A name search alone has no class figures.
        $byName = $this->api()->students(new Request(['search' => 'aar']))->getData(true)['data'];
        $this->assertNull($byName['students'][0]['fee']);
    }

    public function test_a_qr_payment_is_approved_into_a_receipt_or_rejected_with_a_reason(): void
    {
        $u = DB::table('student_details')->where('id', $this->student)->value('user_id');
        $row = fn ($utr) => DB::table('fee_payment_requests')->insertGetId([
            'organization_id' => $this->org, 'student_detail_id' => $this->student, 'user_id' => $u, 'fee_type' => 'academic',
            'amount' => 3000, 'utr' => $utr, 'paid_on' => now()->toDateString(), 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $a = $row('111122223333');
        $b = $row('444455556666');

        $list = $this->api()->qrRequests(new Request())->getData(true)['data'];
        $this->assertSame([$a, $b], array_column($list['requests'], 'id'));
        $this->assertSame(2, $list['stats']['pending']);

        $one = $this->api()->qrRequest($a)->getData(true)['data'];
        $this->assertSame(9000.0, (float) $one['side']['remaining']);

        // What reached the account is what is booked.
        $ok = $this->api()->qrApprove(new Request(['amount' => 2800, 'note' => 'Matched']), $a);
        $this->assertSame(200, $ok->status());
        $this->assertSame(2800.0, (float) DB::table('fee_payments')->value('amount'));
        $this->assertSame('online', DB::table('fee_payments')->value('payment_mode'));
        $this->assertSame('approved', DB::table('fee_payment_requests')->where('id', $a)->value('status'));
        $this->assertCount(1, app(\App\Services\FirebaseNotificationService::class)->sent);

        // Twice is refused.
        $again = $this->api()->qrApprove(new Request(['amount' => 2800]), $a);
        $this->assertSame(422, $again->status());

        // A rejection needs its reason.
        $this->assertSame(422, $this->api()->qrReject(new Request(['reason' => ' ']), $b)->status());
        $no = $this->api()->qrReject(new Request(['reason' => 'Not in our statement']), $b);
        $this->assertSame(200, $no->status());
        $this->assertSame('rejected', DB::table('fee_payment_requests')->where('id', $b)->value('status'));
        $this->assertSame(1, DB::table('fee_payments')->count());

        $done = $this->api()->qrRequests(new Request(['status' => '']))->getData(true)['data'];
        $this->assertSame(0, $done['stats']['pending']);
        $this->assertSame(1, $done['stats']['approved']);
        $this->assertSame(1, $done['stats']['rejected']);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Admin\Fee\FeeConcession;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Organization;
use App\Models\Student\StudentDetail;
use App\Models\User;
use App\Support\FeeReceipt;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The academic fee receipt the student app downloads: it renders as a PDF,
 * and reads the year off the student's own fee structure, net of concession.
 */
class FeeReceiptPdfTest extends TestCase
{
    private int $org = 4;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('address')->nullable();
            $t->string('logo')->nullable();
            $t->string('mobile_number')->nullable();
            $t->string('email')->nullable();
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable();
            $t->string('full_name')->nullable();
            $t->string('father_name')->nullable();
            $t->string('admission_no')->nullable();
            $t->string('roll_no')->nullable();
            $t->timestamps();
        });
        Schema::create('fee_structures', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable();
            $t->string('fee_name')->nullable();
            $t->decimal('amount', 12, 2)->default(0);
            $t->string('fee_type')->default('academic');
            $t->string('academic_year')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('fee_concessions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('student_detail_id');
            $t->string('concession_type')->default('amount');
            $t->decimal('value', 12, 2)->default(0);
            $t->string('fee_type')->default('academic');
            $t->boolean('is_penalty')->default(false);
            $t->string('reason')->nullable();
            $t->timestamps();
        });
        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('student_detail_id');
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable();
            $t->string('fee_type')->default('academic');
            $t->decimal('amount', 12, 2)->default(0);
            $t->decimal('waiver_amount', 12, 2)->default(0);
            $t->decimal('penalty_amount', 12, 2)->default(0);
            $t->string('payment_mode')->nullable();
            $t->date('payment_date')->nullable();
            $t->string('remark')->nullable();
            $t->string('submitted_by')->nullable();
            $t->string('receipt_number')->nullable();
            $t->timestamps();
        });
    }

    private function payment(): FeePayment
    {
        Organization::forceCreate(['id' => $this->org, 'name' => 'Springfield School', 'address' => 'Main Road']);
        $user = User::forceCreate(['name' => 'Raj Kumar', 'email' => 'raj@example.com', 'organization_id' => $this->org]);

        $student = StudentDetail::forceCreate([
            'user_id'         => $user->id,
            'organization_id' => $this->org,
            'standard_id'     => null,
            'section_id'      => null,
            'full_name'       => 'Raj Kumar',
            'admission_no'    => 'ADM-101',
        ]);

        FeeStructure::forceCreate([
            'organization_id' => $this->org,
            'standard_id'     => null,
            'section_id'      => null,
            'fee_name'        => 'Tuition',
            'amount'          => 20000,
            'fee_type'        => 'academic',
            'is_active'       => true,
        ]);

        FeeConcession::forceCreate([
            'organization_id'   => $this->org,
            'student_detail_id' => $student->id,
            'concession_type'   => 'amount',
            'value'             => 2000,
            'fee_type'          => 'academic',
            'is_penalty'        => false,
        ]);

        return FeePayment::forceCreate([
            'organization_id'   => $this->org,
            'student_detail_id' => $student->id,
            'fee_type'          => 'academic',
            'amount'            => 5000,
            'penalty_amount'    => 60,
            'payment_mode'      => 'upi',
            'payment_date'      => '2026-09-12',
            'submitted_by'      => 'Front Desk',
            'receipt_number'    => 'RCT-4-2026-00001',
        ]);
    }

    public function test_the_receipt_reads_the_year_net_of_concession(): void
    {
        $data = FeeReceipt::data($this->payment()->load(FeeReceipt::WITH));

        $this->assertSame(18000.0, $data['yearTotal']);
        $this->assertSame(2000.0, $data['concession']);
        $this->assertSame(5000.0, $data['paidSoFar']);
        $this->assertSame('Front Desk', $data['collectedBy']);
        $this->assertSame('UPI', $data['payMode']);
    }

    public function test_the_receipt_renders_as_a_pdf(): void
    {
        $pdf = FeeReceipt::pdf($this->payment()->load(FeeReceipt::WITH))->output();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }
}

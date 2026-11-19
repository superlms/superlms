<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\AdminStandardController;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Organization;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\User;
use App\Support\FeeReceipt;
use App\Support\SchoolBrand;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * A school under two names (a CBSE Public School for Nursery–5, a UP Board
 * Inter College for 6–12): each class is set to one, and its documents carry
 * that name and logo; the address stays the school's. A school with no
 * second name, or a class on the first, is unchanged.
 */
class SchoolBrandTest extends TestCase
{
    private int $org = 7;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('second_name')->nullable();
            $t->string('address')->nullable();
            $t->string('logo')->nullable();
            $t->string('second_logo')->nullable();
            $t->string('mobile_number')->nullable();
            $t->string('email')->nullable();
            $t->string('education_board')->nullable();
            $t->timestamps();
        });
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
            $t->string('code')->nullable();
            $t->string('board')->nullable();
            $t->unsignedTinyInteger('wing')->default(1);
            $t->integer('order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('name');
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

        Organization::forceCreate([
            'id'          => $this->org,
            'name'        => 'Sunrise Public School',
            'second_name' => 'Sunrise Inter College',
            'logo'        => 'https://cdn.example.com/public-school.png',
            'second_logo' => 'https://cdn.example.com/inter-college.png',
            'address'     => 'Station Road, Agra',
        ]);
    }

    private function classes(): array
    {
        return [
            Standard::forceCreate(['organization_id' => $this->org, 'name' => 'Class 5', 'code' => '05', 'wing' => 1]),
            Standard::forceCreate(['organization_id' => $this->org, 'name' => 'Class 6', 'code' => '06', 'wing' => 2]),
        ];
    }

    public function test_a_class_on_the_second_wing_carries_the_second_name_and_logo(): void
    {
        [$five, $six] = $this->classes();
        $org = Organization::find($this->org);

        $this->assertSame('Sunrise Public School', SchoolBrand::name($org, $five->id));
        $this->assertSame('https://cdn.example.com/public-school.png', SchoolBrand::logo($org, $five));

        $brand = SchoolBrand::org($org, $six->id);
        $this->assertSame('Sunrise Inter College', $brand->name);
        $this->assertSame('https://cdn.example.com/inter-college.png', $brand->logo);
        // The address and the rest stay the school's own…
        $this->assertSame('Station Road, Agra', $brand->address);
        // …and the school itself is not touched.
        $this->assertSame('Sunrise Public School', $org->name);
        $this->assertSame('Sunrise Public School', Organization::find($this->org)->name);

        // No class, a deleted class: the school's own name.
        $this->assertSame('Sunrise Public School', SchoolBrand::name($org, null));
        $this->assertSame('Sunrise Public School', SchoolBrand::name($org, 999));
    }

    public function test_without_a_second_logo_the_class_keeps_the_schools_logo(): void
    {
        [, $six] = $this->classes();
        DB::table('organizations')->where('id', $this->org)->update(['second_logo' => null]);

        $brand = SchoolBrand::org(Organization::find($this->org), $six);
        $this->assertSame('Sunrise Inter College', $brand->name);
        $this->assertSame('https://cdn.example.com/public-school.png', $brand->logo);
    }

    public function test_a_school_with_no_second_name_is_unchanged(): void
    {
        [, $six] = $this->classes();
        DB::table('organizations')->where('id', $this->org)->update(['second_name' => null]);
        $org = Organization::find($this->org);

        $this->assertSame($org, SchoolBrand::org($org, $six->id));
        $this->assertSame([], SchoolBrand::options($org));
    }

    public function test_the_fee_receipt_is_named_by_the_class_the_fee_was_paid_in(): void
    {
        [$five, $six] = $this->classes();
        $user = User::forceCreate(['name' => 'Asha', 'organization_id' => $this->org]);
        // Promoted to Class 6 since; the receipt was for Class 5.
        $student = StudentDetail::forceCreate([
            'user_id' => $user->id, 'organization_id' => $this->org, 'standard_id' => $six->id,
            'full_name' => 'Asha Verma', 'admission_no' => 'ADM-7',
        ]);
        $old = FeePayment::forceCreate([
            'organization_id' => $this->org, 'student_detail_id' => $student->id, 'standard_id' => $five->id,
            'amount' => 1000, 'payment_mode' => 'cash', 'payment_date' => '2026-03-01', 'receipt_number' => 'R-1',
        ]);
        $new = FeePayment::forceCreate([
            'organization_id' => $this->org, 'student_detail_id' => $student->id, 'standard_id' => $six->id,
            'amount' => 1500, 'payment_mode' => 'cash', 'payment_date' => '2026-07-01', 'receipt_number' => 'R-2',
        ]);

        $oldData = FeeReceipt::data($old->load(FeeReceipt::WITH));
        $newData = FeeReceipt::data($new->load(FeeReceipt::WITH));
        $this->assertSame('Sunrise Public School', $oldData['org']->name);
        $this->assertSame('Sunrise Inter College', $newData['org']->name);

        $html = view('pdf.fee-receipt', $newData + ['fontCss' => ''])->render();
        $this->assertStringContainsString('Sunrise Inter College', $html);
        $this->assertStringNotContainsString('Sunrise Public School', $html);
        $this->assertStringContainsString('Station Road, Agra', $html);
    }

    public function test_the_admin_app_sets_a_classes_wing_only_for_a_school_with_two_names(): void
    {
        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 1;
        $admin->organization_id = $this->org;
        $admin->role = 'admin';
        $this->actingAs($admin);
        $api = app(AdminStandardController::class);

        $res = $api->storeStandard(new Request(['name' => 'Class 9', 'code' => '09', 'wing' => 2]));
        $this->assertSame(200, $res->status());
        $this->assertSame(2, $res->getData(true)['data']['wing']);
        $nine = Standard::where('name', 'Class 9')->first();
        $this->assertSame(2, (int) $nine->wing);

        // An edit that sends no wing leaves it.
        $api->updateStandard(new Request(['name' => 'Class 9', 'code' => '09']), $nine->id);
        $this->assertSame(2, (int) $nine->fresh()->wing);

        $lookups = $api->lookups()->getData(true)['data'];
        $this->assertSame(['1' => 'Sunrise Public School', '2' => 'Sunrise Inter College'], $lookups['wings']);

        // One name only: the wing sent is ignored.
        DB::table('organizations')->where('id', $this->org)->update(['second_name' => null]);
        $api->storeStandard(new Request(['name' => 'Class 10', 'code' => '10', 'wing' => 2]));
        $this->assertSame(1, (int) Standard::where('name', 'Class 10')->value('wing'));
    }
}

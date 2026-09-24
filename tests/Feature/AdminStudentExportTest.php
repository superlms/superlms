<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\AdminStudentController;
use App\Models\User;
use App\Support\StudentExport;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The Students export — the panel's and the admin app's: the whole school or
 * one class (and one section of it), as an Excel sheet or a PDF.
 */
class AdminStudentExportTest extends TestCase
{
    private int $org = 4;
    private AdminStudentController $api;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('logo')->nullable();
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('image')->nullable();
            $t->string('role')->nullable();
            $t->boolean('is_active')->default(true);
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('name');
            $t->string('board')->nullable();
            $t->integer('order')->default(0);
            $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('standard_id');
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable();
            foreach (['full_name', 'admission_no', 'roll_no', 'phone', 'gender', 'religion', 'aadhar_no', 'father_name', 'mother_name',
                'board', 'appar_id', 'registration_number', 'state', 'city', 'pincode', 'local_address', 'permanent_address', 'email'] as $c) {
                $t->string($c)->nullable();
            }
            $t->date('dob')->nullable();
            $t->date('date_of_admission')->nullable();
            $t->boolean('transportation_required')->default(false);
            $t->timestamps();
        });
        Schema::create('student_attendances', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('student_detail_id');
            $t->integer('status');
            $t->timestamps();
        });
        Schema::create('fee_structures', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable();
            $t->string('fee_type')->nullable();
            $t->decimal('amount', 10, 2)->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('fee_payments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('student_detail_id');
            $t->string('fee_type')->nullable();
            $t->decimal('amount', 10, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('transportations', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->string('route_name')->nullable();
            $t->decimal('monthly_fee', 10, 2)->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('transportation_students', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('transportation_id');
            $t->unsignedBigInteger('student_detail_id');
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->text('billable_months')->nullable();
            $t->timestamps();
        });
        Schema::create('transport_fee_payments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('student_detail_id');
            $t->decimal('amount', 10, 2)->default(0);
            $t->timestamps();
        });

        DB::table('organizations')->insert(['id' => $this->org, 'name' => 'The Demo School']);
        DB::table('standards')->insert([
            ['id' => 1, 'organization_id' => $this->org, 'name' => 'Class 1', 'order' => 1],
            ['id' => 2, 'organization_id' => $this->org, 'name' => 'Class 2', 'order' => 2],
            ['id' => 3, 'organization_id' => 99, 'name' => 'Elsewhere', 'order' => 1],
        ]);
        DB::table('sections')->insert([
            ['id' => 10, 'standard_id' => 1, 'name' => 'A'],
            ['id' => 11, 'standard_id' => 1, 'name' => 'B'],
            ['id' => 20, 'standard_id' => 2, 'name' => 'A'],
        ]);
        foreach ([['Aarav', 1, 10], ['Bina', 1, 11], ['Chetan', 2, 20]] as $i => [$name, $class, $section]) {
            $user = User::forceCreate(['name' => $name, 'email' => strtolower($name) . '@s.test', 'organization_id' => $this->org, 'role' => 'user']);
            DB::table('student_details')->insert([
                'user_id' => $user->id, 'organization_id' => $this->org, 'standard_id' => $class, 'section_id' => $section,
                'full_name' => $name, 'admission_no' => 'ADM-' . ($i + 1), 'roll_no' => (string) ($i + 1),
            ]);
        }

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 900;
        $admin->organization_id = $this->org;
        $admin->role = 'admin';
        $this->actingAs($admin);

        $this->api = app(AdminStudentController::class);
    }

    public function test_the_whole_school_or_one_class_or_one_section(): void
    {
        $this->assertCount(3, StudentExport::data($this->org)[1]);
        $this->assertSame(['Aarav', 'Bina'], array_column(StudentExport::data($this->org, 1)[1], 'Full Name'));
        $this->assertSame(['Bina'], array_column(StudentExport::data($this->org, 1, 11)[1], 'Full Name'));
        $this->assertSame(['Class 1 - A', 'Class 1 - B'], array_keys(StudentExport::data($this->org, 1)[2]));
        $this->assertSame('class-1-b', StudentExport::slug(1, 11));
        $this->assertSame('all', StudentExport::slug(null, null));
    }

    public function test_an_excel_sheet_comes_back_as_the_download(): void
    {
        $res = $this->api->export(Request::create('/', 'GET', ['format' => 'xlsx', 'class_id' => 1]));

        $this->assertSame(200, $res->getStatusCode());
        $this->assertStringContainsString('spreadsheetml', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('students_class-1_', $res->headers->get('Content-Disposition'));
        $this->assertSame('2', $res->headers->get('X-Export-Count'));
        $this->assertStringStartsWith('PK', $res->getContent());
    }

    public function test_a_pdf_comes_back_as_the_download(): void
    {
        $res = $this->api->export(Request::create('/', 'GET', ['format' => 'pdf']));

        $this->assertSame(200, $res->getStatusCode());
        $this->assertSame('application/pdf', $res->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $res->getContent());
    }

    public function test_another_school_s_class_or_an_empty_one_is_refused(): void
    {
        $this->assertSame(404, $this->api->export(Request::create('/', 'GET', ['format' => 'xlsx', 'class_id' => 3]))->getStatusCode());

        DB::table('student_details')->where('standard_id', 2)->delete();
        $empty = $this->api->export(Request::create('/', 'GET', ['format' => 'pdf', 'class_id' => 2]));
        $this->assertSame(422, $empty->getStatusCode());
        $this->assertSame('No students in this class to export.', $empty->getData(true)['message']);
    }
}

<?php

namespace Tests\Feature;

use App\Livewire\Admin\Standard as StandardPage;
use App\Livewire\SuperAdmin\Schools;
use App\Models\Student\Standard;
use App\Models\User;
use App\Services\FeeAutopay;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Where a school's second name is set and used: the Super Admin enters it and
 * its logo under the school's Custom Fee tab, and the school's class form then
 * asks which of the two names each class carries.
 */
class SchoolBrandPanelsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('o', 32))]);

        $tracked = new \ReflectionProperty(FeeAutopay::class, 'tracked');
        $tracked->setValue(null, null);

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('mobile_number')->nullable();
            $t->string('state')->nullable(); $t->string('address')->nullable(); $t->string('logo')->nullable();
            $t->boolean('status')->default(true); $t->string('school_code')->nullable(); $t->string('affiliation_no')->nullable();
            $t->string('medium')->nullable(); $t->string('education_board')->nullable(); $t->string('udise_number')->nullable();
            $t->string('serial_number')->nullable(); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable(); $t->text('permissions')->nullable();
            $t->string('mobile_number')->nullable(); $t->unsignedBigInteger('organization_id')->nullable();
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable(); $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->unsignedBigInteger('user_id')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->string('code')->nullable();
            $t->string('file_path')->nullable(); $t->string('board')->nullable(); $t->integer('order')->default(0);
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('name'); $t->string('code')->nullable(); $t->text('description')->nullable();
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->string('code')->nullable();
            $t->text('description')->nullable(); $t->string('image')->nullable(); $t->string('detail_image')->nullable();
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('standard_subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('standard_id'); $t->unsignedBigInteger('subject_id');
            $t->unsignedBigInteger('organization_id')->nullable(); $t->boolean('is_mandatory')->default(true); $t->timestamps();
        });
        Schema::create('section_subjects', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('section_id'); $t->unsignedBigInteger('subject_id'); $t->unsignedBigInteger('standard_id');
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        (require base_path('database/migrations/2026_06_17_000001_create_organization_payment_settings_table.php'))->up();
        (require base_path('database/migrations/2026_11_02_140000_create_fee_autopay_tables.php'))->up();
        // The columns this feature adds, by its own migration.
        (require base_path('database/migrations/2026_11_19_120000_add_second_name_to_organizations_and_wing_to_standards.php'))->up();

        DB::table('organizations')->insert(['id' => 1, 'name' => 'Sunrise Public School', 'education_board' => 'CBSE']);
    }

    private function actAsSuperAdmin(): void
    {
        $super = new User(['name' => 'Super', 'email' => 'super@example.com']);
        $super->id = 901;
        $super->role = 'super-admin';
        $super->permissions = [];
        $this->actingAs($super);
    }

    private function actAsSchoolAdmin(): void
    {
        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 1;
        $admin->organization_id = 1;
        $admin->role = 'admin';
        $this->actingAs($admin);
    }

    public function test_the_super_admin_sets_the_second_name_and_logo_under_custom_fee(): void
    {
        Storage::fake('s3');
        $this->actAsSuperAdmin();

        $page = Livewire::test(Schools::class)
            ->call('viewSchoolDetail', 1)
            ->call('setDetailTab', 'custom_fee')
            ->assertSee('Second Name &amp; Logo', false)
            ->assertSee('Save Second Name');

        // A logo with no name is refused.
        $page->set('secondLogo', UploadedFile::fake()->image('college.png', 120, 120))
            ->call('saveSecondName')
            ->assertHasErrors('secondName');

        $page->set('secondName', '  Sunrise Inter College ')
            ->call('saveSecondName')
            ->assertHasNoErrors();

        $row = DB::table('organizations')->find(1);
        $this->assertSame('Sunrise Inter College', $row->second_name);
        $this->assertNotEmpty($row->second_logo);
        $this->assertSame('Sunrise Public School', $row->name);
        $page->assertSet('secondName', 'Sunrise Inter College')->assertSee('On · 0 classes set to it');

        // Blank: one name for every class again, and the logo goes with it.
        $page->set('secondName', '')->call('saveSecondName');
        $row = DB::table('organizations')->find(1);
        $this->assertNull($row->second_name);
        $this->assertNull($row->second_logo);
    }

    public function test_the_class_form_asks_for_the_wing_only_when_the_school_has_two_names(): void
    {
        $this->actAsSchoolAdmin();

        // One name: no Wing on the form, and the class stays on the first.
        Livewire::test(StandardPage::class)
            ->set('addType', 'class')->set('openForm', true)
            ->assertDontSee('Wing')
            ->set('standardName', 'Class 1')->set('standardCode', '01')->set('standardWing', 2)
            ->call('saveStandard')->assertHasNoErrors();
        $this->assertSame(1, (int) Standard::where('name', 'Class 1')->value('wing'));

        DB::table('organizations')->where('id', 1)->update(['second_name' => 'Sunrise Inter College']);

        $page = Livewire::test(StandardPage::class)
            ->set('addType', 'class')->set('openForm', true)
            ->assertSee('Wing')
            ->assertSee('Sunrise Public School')
            ->assertSee('Sunrise Inter College');

        $page->set('standardName', 'Class 6')->set('standardCode', '06')->set('standardWing', 2)
            ->call('saveStandard')->assertHasNoErrors();
        $six = Standard::where('name', 'Class 6')->first();
        $this->assertSame(2, (int) $six->wing);

        // Editing opens on the class's own wing, and the list names it.
        $page->call('editStandard', $six->id)->assertSet('standardWing', 2);
        $html = Livewire::test(StandardPage::class)->html();
        $this->assertStringContainsString('Sunrise Inter College', $html);
    }
}

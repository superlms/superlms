<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\AdminStandardController;
use App\Models\Student\Section;
use App\Models\Student\SectionSubject;
use App\Models\Student\Standard;
use App\Models\Student\StandardSubject;
use App\Models\Student\Subject;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The admin app's Standard keeps the panel's rules: a class or section with
 * students in it is not deleted, a section's name is once per class and needs
 * no code, and adding a subject whose name the class already has links its
 * missing sections instead of refusing — as long as the older builds that
 * send codes still save.
 */
class AdminStandardPanelRulesTest extends TestCase
{
    private int $org = 12;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('education_board')->nullable(); $t->timestamps();
        });
        Schema::create('standards', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name'); $t->string('code')->nullable();
            $t->string('board')->nullable(); $t->integer('order')->default(0); $t->boolean('is_active')->default(true); $t->timestamps();
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
        Schema::create('student_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->nullable(); $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable(); $t->unsignedBigInteger('section_id')->nullable(); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->boolean('is_active')->default(true); $t->timestamps();
        });

        DB::table('organizations')->insert(['id' => $this->org, 'education_board' => 'CBSE']);

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 1;
        $admin->organization_id = $this->org;
        $admin->role = 'admin';
        $this->actingAs($admin);
    }

    private function api(): AdminStandardController
    {
        return app(AdminStandardController::class);
    }

    public function test_a_class_or_section_with_students_is_not_deleted(): void
    {
        $class = Standard::create(['organization_id' => $this->org, 'name' => 'Class 1', 'code' => '01']);
        $a = Section::create(['standard_id' => $class->id, 'organization_id' => $this->org, 'name' => 'A']);
        DB::table('student_details')->insert(['organization_id' => $this->org, 'standard_id' => $class->id, 'section_id' => $a->id]);

        $sec = $this->api()->deleteSection($a->id);
        $this->assertSame(422, $sec->status());
        $this->assertStringStartsWith('Students are assigned to this section.', $sec->getData(true)['message']);

        $cls = $this->api()->deleteStandard($class->id);
        $this->assertSame(422, $cls->status());
        $this->assertStringStartsWith('Students are assigned to this class.', $cls->getData(true)['message']);

        $this->assertSame(1, Section::count());
        $this->assertSame(1, Standard::count());
    }

    public function test_a_section_needs_no_code_and_its_name_is_once_per_class(): void
    {
        $class = Standard::create(['organization_id' => $this->org, 'name' => 'Class 1', 'code' => '01']);

        $ok = $this->api()->storeSection(new Request(['name' => 'A', 'standard_id' => $class->id]));
        $this->assertSame(200, $ok->status());

        // The same name with another code is still the same section.
        $dup = $this->api()->storeSection(new Request(['name' => 'A', 'code' => 'X', 'standard_id' => $class->id]));
        $this->assertSame(422, $dup->status());
        $this->assertSame('A section with this name already exists in the selected class.', $dup->getData(true)['message']);

        // An older build's code is still kept, and an edit without one leaves it.
        $b = $this->api()->storeSection(new Request(['name' => 'B', 'code' => 'B1', 'standard_id' => $class->id]))->getData(true)['data'];
        $this->api()->updateSection(new Request(['name' => 'B2', 'standard_id' => $class->id]), $b['id']);
        $this->assertSame('B1', Section::find($b['id'])->code);
        $this->assertSame('B2', Section::find($b['id'])->name);
    }

    public function test_a_subject_the_class_has_links_its_missing_sections(): void
    {
        $class = Standard::create(['organization_id' => $this->org, 'name' => 'Class 1', 'code' => '01']);
        $a = Section::create(['standard_id' => $class->id, 'organization_id' => $this->org, 'name' => 'A']);
        $b = Section::create(['standard_id' => $class->id, 'organization_id' => $this->org, 'name' => 'B']);

        $first = $this->api()->storeSubject(new Request(['name' => 'Maths', 'standard_id' => $class->id, 'section_ids' => [$a->id]]));
        $this->assertSame(200, $first->status());
        $this->assertSame('Subject saved successfully!', $first->getData(true)['message']);

        // Maths again for A and B: the same subject, now in B too.
        $again = $this->api()->storeSubject(new Request(['name' => 'Maths', 'standard_id' => $class->id, 'section_ids' => [$a->id, $b->id]]));
        $this->assertSame(200, $again->status());
        $this->assertSame(1, Subject::count());
        $this->assertSame([$a->id, $b->id], SectionSubject::orderBy('section_id')->pluck('section_id')->map(fn ($v) => (int) $v)->all());
        $this->assertSame(1, StandardSubject::count());

        // Already in every section picked: a duplicate.
        $dup = $this->api()->storeSubject(new Request(['name' => 'Maths', 'standard_id' => $class->id, 'section_ids' => [$b->id]]));
        $this->assertSame(422, $dup->status());
        $this->assertSame('A subject with this name already exists in the selected section(s).', $dup->getData(true)['message']);
    }

    public function test_the_class_list_names_its_sections_and_lookups_suggest_the_next_code(): void
    {
        $class = Standard::create(['organization_id' => $this->org, 'name' => 'Class 1', 'code' => '01', 'order' => 1]);
        Section::create(['standard_id' => $class->id, 'organization_id' => $this->org, 'name' => 'A']);
        Section::create(['standard_id' => $class->id, 'organization_id' => $this->org, 'name' => 'B']);

        $list = $this->api()->standards(new Request())->getData(true)['data'];
        $this->assertSame(['A', 'B'], $list['standards'][0]['section_names']);

        $this->assertSame('02', $this->api()->lookups()->getData(true)['data']['next_code']);

        $long = $this->api()->storeStandard(new Request(['name' => 'Class 2', 'code' => '12345678901']));
        $this->assertSame(422, $long->status());
        $this->assertSame('Class code may not be longer than 10 characters.', $long->getData(true)['message']);
    }
}

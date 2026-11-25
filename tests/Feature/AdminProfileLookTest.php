<?php

namespace Tests\Feature;

use App\Livewire\Components\Profile;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * School Admin → Profile in the Super Admin's My Profile look (2026-11-25):
 * Home's skin and header, School Profile / School Info as chips, white cards
 * with icon titles, the school's figures, its details and bank details; and
 * School Info's sections, management team and documents.
 */
class AdminProfileLookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('p', 32))]);

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('mobile_number')->nullable(); $t->string('logo')->nullable();
            $t->string('address')->nullable(); $t->string('state')->nullable(); $t->string('education_board')->nullable(); $t->string('school_code')->nullable();
            $t->string('affiliation_no')->nullable(); $t->string('bank_name')->nullable(); $t->string('bank_ifsc')->nullable(); $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable(); $t->unsignedBigInteger('organization_id')->nullable();
            $t->text('permissions')->nullable(); $t->string('mobile_number')->nullable(); $t->string('image')->nullable(); $t->timestamps();
        });
        Schema::create('school_infos', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->text('about_school')->nullable(); $t->text('website_info')->nullable(); $t->string('website_url')->nullable();
            $t->string('school_mobile')->nullable(); $t->string('school_email')->nullable(); $t->text('school_address')->nullable(); $t->text('school_document_text')->nullable();
            $t->text('usm_vision')->nullable(); $t->text('usm_mission')->nullable(); $t->text('usm_values')->nullable(); $t->text('usm_goals')->nullable();
            $t->text('custom_sections')->nullable(); $t->timestamps();
        });
        Schema::create('school_management_teams', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('school_info_id'); $t->string('name'); $t->string('designation')->nullable(); $t->string('photo_path')->nullable(); $t->integer('sort_order')->default(0); $t->timestamps();
        });
        Schema::create('school_documents', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('school_info_id'); $t->string('title'); $t->string('file_path'); $t->string('file_type')->nullable(); $t->integer('sort_order')->default(0); $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('organization_id'); $t->timestamps(); });

        DB::table('organizations')->insert(['id' => 4, 'name' => 'Adarsh Inter College', 'email' => 'office@adarsh.in', 'mobile_number' => '9876543210',
            'education_board' => 'UP Board', 'school_code' => 'ASIC', 'affiliation_no' => '1234', 'bank_name' => 'SBI', 'bank_ifsc' => 'SBIN0001']);
        DB::table('student_details')->insert([['organization_id' => 4], ['organization_id' => 4], ['organization_id' => 9]]);
        $info = DB::table('school_infos')->insertGetId(['organization_id' => 4, 'about_school' => 'A school by the river.', 'usm_vision' => 'Every child reads.',
            'website_url' => 'https://adarsh.in', 'custom_sections' => json_encode([['title' => 'Our History', 'description' => 'Since 1985.']]), 'updated_at' => '2026-11-20 10:00:00']);
        DB::table('school_management_teams')->insert(['school_info_id' => $info, 'name' => 'R. K. Sharma', 'designation' => 'Principal']);
        DB::table('school_documents')->insert(['school_info_id' => $info, 'title' => 'Fee Rules', 'file_path' => 'https://example.test/fee.pdf', 'file_type' => 'pdf']);
    }

    private function signIn(string $role = 'admin'): User
    {
        $id = DB::table('users')->insertGetId(['name' => 'Head', 'email' => 'head@adarsh.in', 'role' => $role, 'organization_id' => 4,
            'permissions' => json_encode(['admin.student'])]);
        $user = User::find($id);
        $this->actingAs($user);

        return $user;
    }

    public function test_school_profile_is_in_the_super_admin_profile_look(): void
    {
        $this->signIn();
        $html = Livewire::test(Profile::class)->html();
        $text = preg_replace('/\s+/', ' ', strip_tags($html));

        foreach (['sa-skin sa-home', 'class="sk-head"', 'class="an-chips"', 'class="an-chip on"', 'class="pf-figs', 'class="pf-main"', 'class="pf-icon"'] as $look) {
            $this->assertStringContainsString($look, $html);
        }
        foreach (['Profile', 'School Profile', 'School Info', 'Adarsh Inter College', 'UP Board', 'Code ASIC',
                  'School Details', 'Affiliation Number 1234', 'Bank Details', 'IFSC Code SBIN0001', 'adarsh.in', 'Change Password', 'Updated · 20 Nov 2026'] as $seen) {
            $this->assertStringContainsString($seen, $text, "Profile shows \"{$seen}\"");
        }
        // This school's two students, not the other school's one.
        $this->assertMatchesRegularExpression('/Students\s*2\s*Teachers/', $text);
        // No more of the old blue/indigo look.
        $this->assertStringNotContainsString('bg-blue-600', $html);
        $this->assertStringNotContainsString('from-indigo-50', $html);
    }

    public function test_school_info_and_its_form(): void
    {
        $this->signIn();
        $page = Livewire::test(Profile::class)->call('showTab', 'info');
        $text = preg_replace('/\s+/', ' ', strip_tags($page->html()));
        foreach (['About Our School', 'A school by the river.', 'Vision', 'Our History', 'Since 1985.', 'Management Team', 'R. K. Sharma', 'Principal',
                  'School Documents', 'Fee Rules', '3 sections', '1 in management', '1 document', 'Edit'] as $seen) {
            $this->assertStringContainsString($seen, $text, "School Info shows \"{$seen}\"");
        }

        $html = $page->call('setInfoMode', 'edit')->html();
        $this->assertStringContainsString('wire:model="aboutSchool"', $html);
        $this->assertStringContainsString('class="pf-input"', $html);
        $this->assertStringContainsString('wire:click="saveSchoolInfo"', $html);
        $this->assertStringNotContainsString('bg-gradient-to-r from-indigo-50', $html);
    }

    public function test_a_sub_admin_sees_their_own_card(): void
    {
        $this->signIn('sub-admin');
        $text = preg_replace('/\s+/', ' ', strip_tags(Livewire::test(Profile::class)->html()));
        $this->assertStringContainsString('Sub-admin', $text);
        $this->assertStringContainsString('Your access', $text);
    }
}

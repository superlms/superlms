<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Enquiry;
use App\Livewire\SuperAdmin\QuickLinks;
use App\Livewire\SuperAdmin\Reports;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin pages in the school panel's look (user's asks of 2026-10-11):
 * Quick Links' clock as the school panel's and no link count, Reports in the
 * dashboard's style, Website Enquiries listed as the school's Enquiries.
 */
class SuperAdminPagesLookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('p', 32))]);
        Carbon::setTestNow('2026-10-11 10:00:00');

        $user = new User(['name' => 'Super', 'email' => 'super@example.com']);
        $user->id = 902;
        $user->role = 'super-admin';
        $user->permissions = [];
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_quick_links_show_the_day_date_and_time_and_no_link_count(): void
    {
        $html = Livewire::test(QuickLinks::class)->html();

        $this->assertStringNotContainsString('links</span>', $html);
        $this->assertStringContainsString('x-text="day"', $html);
        $this->assertStringContainsString('x-text="time"', $html);
        $this->assertStringNotContainsString('bg-indigo-50 border border-indigo-100', $html);
    }

    public function test_reports_keep_their_figures_in_the_dashboard_look(): void
    {
        // Every figure falls back to 0 when its table is missing, so the page
        // renders on the bare test database.
        $html = Livewire::test(Reports::class)->html();

        preg_match_all('/<h2[^>]*>([^<]+)<\/h2>/', $html, $m);
        $this->assertSame(['Today', 'Oct 2026', 'All-Time Totals', 'Day by Day'], array_map('trim', $m[1]));
        foreach (['New Students', 'Platform Revenue', 'Fees Collected', 'Support Tickets', 'Enquiries'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
        $this->assertStringNotContainsString('🎓', $html);
    }

    public function test_users_show_functionalities_as_plain_text_and_the_views_edit_opens_the_form(): void
    {
        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            foreach (['name', 'email', 'role', 'mobile_number', 'alternative_mobile', 'address', 'gender', 'image', 'password'] as $c) {
                $t->string($c)->nullable();
            }
            $t->date('dob')->nullable();
            $t->date('date_of_joining')->nullable();
            $t->boolean('is_active')->default(true);
            $t->text('permissions')->nullable();
            $t->unsignedBigInteger('allowed_organization_id')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamp('last_login_at')->nullable();
            $t->timestamps();
        });
        $id = DB::table('users')->insertGetId([
            'name' => 'Priya Verma', 'email' => 'priya@example.com', 'role' => 'sub-super-admin',
            'mobile_number' => '9876501234', 'is_active' => 1,
            'permissions' => json_encode(['super-admin.schools', 'super-admin.students']),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $page = Livewire::test(\App\Livewire\SuperAdmin\Users::class);
        $list = preg_replace('/\s+/', ' ', strip_tags($page->html()));
        $this->assertStringContainsString('2 functionalities', $list);
        $this->assertStringNotContainsString('bg-indigo-50 text-indigo-600', $page->html());

        $page->call('view', $id);
        $this->assertStringContainsString('Schools · Students', preg_replace('/\s+/', ' ', strip_tags($page->html())));

        $page->call('editFromView', $id)
            ->assertSet('showViewPanel', false)
            ->assertSet('showPanel', true)
            ->assertSet('editId', $id)
            ->assertSet('fullName', 'Priya Verma');
    }

    public function test_support_tickets_and_ratings_are_listed_as_the_school_panels_enquiries(): void
    {
        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('logo')->nullable();
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('role')->nullable();
            $t->timestamps();
        });
        Schema::create('contact_super_admins', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('topic')->nullable();
            $t->text('admin_query')->nullable();
            $t->string('image')->nullable();
            $t->text('super_admin_text')->nullable();
            $t->boolean('super_admin_reply')->default(false);
            $t->string('super_admin_attachment')->nullable();
            $t->timestamps();
        });
        Schema::create('rate_lms', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->text('feedback')->nullable();
            $t->unsignedTinyInteger('rating');
            $t->unsignedTinyInteger('status')->default(1);
            $t->timestamps();
        });
        DB::table('organizations')->insert(['id' => 1, 'name' => 'Sunrise Public School', 'email' => 'office@sunrise.edu']);
        DB::table('users')->insert(['id' => 5, 'name' => 'Ravi Admin', 'email' => 'ravi@sunrise.edu', 'role' => 'admin']);
        DB::table('contact_super_admins')->insert([
            'user_id' => 5, 'organization_id' => 1, 'topic' => 'Fee receipt not printing',
            'admin_query' => 'The receipt shows a blank page.', 'image' => 'https://example.com/a.png',
            'super_admin_reply' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('rate_lms')->insert([
            'organization_id' => 1, 'feedback' => 'Easy to use for our staff.', 'rating' => 4, 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $support = preg_replace('/\s+/', ' ', strip_tags(Livewire::test(\App\Livewire\SuperAdmin\Support::class)->html()));
        $support = preg_replace('/\s*·\s*/u', ' · ', $support);
        $this->assertStringContainsString('S.No Ticket Actions', $support);
        $this->assertStringContainsString('1 Fee receipt not printing The receipt shows a blank page. Sunrise Public School · Ravi Admin · ravi@sunrise.edu', $support);
        $this->assertStringContainsString('Pending · Attachment', $support);

        $rating = preg_replace('/\s+/', ' ', strip_tags(Livewire::test(\App\Livewire\SuperAdmin\Rating::class)->html()));
        $rating = preg_replace('/\s*·\s*/u', ' · ', $rating);
        $this->assertStringContainsString('S.No Review Actions', $rating);
        $this->assertStringContainsString('1 Sunrise Public School Easy to use for our staff. ★★★★★ 4/5 · office@sunrise.edu', $rating);
    }

    public function test_the_privacy_policy_view_is_a_plain_document(): void
    {
        Schema::create('privacy_policies', function (Blueprint $t) {
            $t->id();
            $t->json('metadata')->nullable();
            $t->date('last_updated')->nullable();
            $t->timestamps();
        });
        DB::table('privacy_policies')->insert([
            'metadata' => json_encode(['sections' => [
                ['head' => 'Information We Collect', 'desc' => 'Name and email.'],
                ['head' => '', 'desc' => ''],
                ['head' => 'How We Use It', 'desc' => 'To run the service.'],
            ]]),
            'last_updated' => '2026-10-01', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $html = Livewire::test(\App\Livewire\SuperAdmin\PrivacyPolicy::class)->html();
        $text = preg_replace('/\s+/', ' ', strip_tags($html));

        $this->assertStringContainsString('1.Information We Collect Name and email.', $text);
        $this->assertStringContainsString('2.How We Use It To run the service.', $text);
        $this->assertStringContainsString('Last updated 01 Oct 2026', $text);
        $this->assertStringNotContainsString('from-green-50 to-emerald-50', $html);
    }

    public function test_website_enquiries_are_listed_as_the_school_panels_enquiries(): void
    {
        Schema::create('website_demos', function (Blueprint $t) {
            $t->id();
            foreach (['full_name', 'school_name', 'phone', 'email', 'city', 'no_of_students', 'role', 'preferred_time', 'remark'] as $c) {
                $t->string($c)->nullable();
            }
            $t->date('preferred_date')->nullable();
            $t->timestamps();
        });
        Schema::create('website_contacts', function (Blueprint $t) {
            $t->id();
            foreach (['full_name', 'email', 'phone_number', 'school_name', 'subject', 'remark'] as $c) {
                $t->string($c)->nullable();
            }
            $t->text('description')->nullable();
            $t->timestamps();
        });
        DB::table('website_demos')->insert([
            'full_name' => 'Ravi Kumar', 'school_name' => 'Sunrise Public School', 'phone' => '9876543210',
            'email' => 'ravi@example.com', 'city' => 'Agra', 'no_of_students' => '600', 'role' => 'Principal',
            'preferred_date' => '2026-10-13', 'preferred_time' => '4:00 PM', 'remark' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('website_contacts')->insert([
            'full_name' => 'Meena', 'email' => 'meena@example.com', 'subject' => 'Pricing for 3 schools',
            'description' => 'Please share the plans.', 'remark' => 'Mailed the plans',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $demo = preg_replace('/\s+/', ' ', strip_tags(Livewire::test(Enquiry::class)->html()));
        $this->assertStringContainsString('S.No Enquiry Actions', $demo);
        $this->assertStringContainsString('1 Sunrise Public School Principal · 600 students · Agra Ravi Kumar', $demo);
        $this->assertStringContainsString('Pending', $demo);
        $this->assertStringContainsString('Call Tue, 13 Oct 2026 · 4:00 PM', $demo);

        $contact = preg_replace('/\s+/', ' ', strip_tags(Livewire::test(Enquiry::class)->call('switchTab', 'contact')->html()));
        $this->assertStringContainsString('1 Pricing for 3 schools Please share the plans. Meena', $contact);
        $this->assertStringContainsString('Remarked', $contact);
    }
}

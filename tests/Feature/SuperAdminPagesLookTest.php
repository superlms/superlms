<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\AboutApp;
use App\Livewire\SuperAdmin\BecomeExecutive;
use App\Livewire\SuperAdmin\Careers;
use App\Livewire\SuperAdmin\Documents;
use App\Livewire\SuperAdmin\Enquiry;
use App\Livewire\SuperAdmin\Faqs;
use App\Livewire\SuperAdmin\QuickLinks;
use App\Livewire\SuperAdmin\Reports;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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

        // View and Reply in the Students view's look: plain label / value rows.
        $page = Livewire::test(\App\Livewire\SuperAdmin\Support::class)->call('viewSupport', 1);
        $view = preg_replace('/\s+/', ' ', strip_tags($page->html()));
        $this->assertStringContainsString('School Sunrise Public School Submitted by Ravi Admin Email ravi@sunrise.edu', $view);
        $this->assertStringContainsString('Query The receipt shows a blank page. Your Reply No reply sent yet', $view);
        $reply = preg_replace('/\s+/', ' ', strip_tags($page->call('openReplyModal', 1)->html()));
        $this->assertStringContainsString('Reply to Support Fee receipt not printing · Ravi Admin · Sunrise Public School', $reply);
        $this->assertStringNotContainsString('Replying to', $reply);

        $rating = preg_replace('/\s+/', ' ', strip_tags(Livewire::test(\App\Livewire\SuperAdmin\Rating::class)->html()));
        $rating = preg_replace('/\s*·\s*/u', ' · ', $rating);
        $this->assertStringContainsString('S.No Review Actions', $rating);
        $this->assertStringContainsString('1 Sunrise Public School Easy to use for our staff. ★★★★★ 4/5 · office@sunrise.edu', $rating);
    }

    public function test_push_notifications_list_on_the_page_and_are_added_from_a_slide_in(): void
    {
        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name')->nullable();
            $t->string('email')->nullable();
            $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('user_fcm_tokens', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('token')->nullable();
            $t->string('platform')->nullable();
            $t->timestamps();
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->string('notifiable_type');
            $t->unsignedBigInteger('notifiable_id');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
        Schema::create('push_notification_campaigns', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('body');
            $t->string('audience_scope')->default('all');
            $t->string('audience_role')->nullable();
            $t->json('audience_roles')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->string('screen')->nullable();
            $t->unsignedInteger('recipient_count')->default(0);
            $t->unsignedInteger('device_count')->default(0);
            $t->unsignedInteger('web_count')->default(0);
            $t->json('device_breakdown')->nullable();
            $t->boolean('delivered')->default(false);
            $t->unsignedBigInteger('sent_by')->default(0);
            $t->timestamps();
        });
        DB::table('users')->insert(['id' => 31, 'name' => 'Asha', 'role' => 'user', 'organization_id' => 1]);
        DB::table('student_details')->insert(['user_id' => 31, 'organization_id' => 1]);
        DB::table('push_notification_campaigns')->insert([
            'title' => 'Exam week', 'body' => 'Papers start Monday.', 'audience_scope' => 'all',
            'audience_role' => 'both', 'audience_roles' => json_encode(['students', 'teachers']),
            'recipient_count' => 117, 'device_count' => 0, 'delivered' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->mock(\App\Services\FirebaseNotificationService::class, fn ($m) => $m->shouldReceive('notifyUserIds')->andReturn(false));

        $page = Livewire::test(\App\Livewire\SuperAdmin\PushNotification::class);
        $html = $page->html();
        $text = preg_replace('/\s+/', ' ', strip_tags($html));

        // The list is the page; the form only comes with Add Notification.
        $this->assertStringContainsString('Add Notification', $text);
        $this->assertStringNotContainsString('Who should receive this?', $text);
        $this->assertStringContainsString('Exam week Papers start Monday. All Schools Students, Teachers 117 0 Web only', $text);
        $this->assertMatchesRegularExpression('#<p class="text-gray-600">All Schools</p>\s*<p class="text-gray-400 mt-0.5 whitespace-nowrap">Students, Teachers</p>#', $html);
        $this->assertStringContainsString('<span class="text-amber-600">Web only</span>', $html);
        $this->assertStringNotContainsString('rounded-full bg-amber-50', $html);
        $this->assertStringNotContainsString('Reaches recipients on both', $text);

        // The detail is a slide-in in the Announcement view's look.
        $detail = preg_replace('/\s+/', ' ', strip_tags($page->call('viewCampaign', 1)->html()));
        $detail = preg_replace('/\s*·\s*/u', ' · ', $detail);
        $this->assertStringContainsString('Notification Details', $detail);
        $this->assertStringContainsString('Audience All Schools Students, Teachers Reach 117 recipients · 0 app devices · 0 web inboxes', $detail);
        $page->call('closeView');

        $page->call('openCompose')
            ->assertSet('composing', true)
            ->assertSee('Who should receive this?')
            ->assertSeeHtml('type="radio" wire:model.live="audienceScope" value="all"')
            ->assertSeeHtml('type="checkbox" wire:click="toggleRole(\'admins\')"')
            ->call('toggleRole', 'admins')
            ->assertSet('audienceRoles', ['students', 'teachers', 'admins'])
            ->set('title', 'Holiday')
            ->set('body', 'School closed on Friday.')
            ->set('audienceRoles', ['students'])
            ->call('review')
            ->assertSet('confirming', true)
            ->assertSet('previewRecipients', 1)
            ->call('send')
            ->assertSet('composing', false)
            ->assertSet('confirming', false);

        $this->assertSame(2, DB::table('push_notification_campaigns')->count());
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', 31)->count());
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
        $this->assertStringContainsString('class="min-h-screen bg-white"', $html);
        $this->assertStringContainsString('<p class="text-sm text-black leading-relaxed break-words mt-2">', $html);
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

        // Remark is a reply icon; the view is in the Students view's look.
        $list = Livewire::test(Enquiry::class);
        $this->assertStringContainsString('M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6', $list->html());
        $this->assertStringNotContainsString('M11 5H6a2 2 0 00-2 2v11', $list->html());
        $view = preg_replace('/\s+/', ' ', strip_tags($list->call('viewEnquiry', 1)->html()));
        $this->assertStringContainsString('Ravi Kumar Demo request · Pending', $view);
        $this->assertStringContainsString('Email ravi@example.com Phone 9876543210 School Sunrise Public School City Agra Role Principal Number of Students 600 Scheduled Call Tuesday, 13 Oct 2026 · 4:00 PM', $view);
        $this->assertStringContainsString('Remark No remark added yet', $view);

        $contact = preg_replace('/\s+/', ' ', strip_tags(Livewire::test(Enquiry::class)->call('switchTab', 'contact')->html()));
        $this->assertStringContainsString('1 Pricing for 3 schools Please share the plans. Meena', $contact);
        $this->assertStringContainsString('Remarked', $contact);
    }

    public function test_careers_jobs_and_applications_are_listed_as_website_enquiries(): void
    {
        Schema::create('website_pages', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->json('metadata')->nullable();
            $t->date('last_updated')->nullable();
            $t->timestamps();
        });
        Schema::create('career_applications', function (Blueprint $t) {
            $t->id();
            foreach (['job_role', 'full_name', 'email', 'mobile', 'qualification', 'experience', 'document_path', 'status'] as $c) {
                $t->string($c)->nullable();
            }
            $t->text('address')->nullable();
            $t->text('description')->nullable();
            $t->timestamps();
        });
        DB::table('website_pages')->insert([
            'slug' => 'careers',
            'metadata' => json_encode(['jobs' => [[
                'role' => 'Sales Executive', 'department' => 'Sales', 'location' => 'Aligarh', 'type' => 'Full-time', 'salary' => '3 LPA',
            ]]]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('career_applications')->insert([
            'job_role' => 'Sales Executive', 'full_name' => 'Asha Verma', 'email' => 'asha@example.com', 'mobile' => '9000000001',
            'qualification' => 'MBA', 'experience' => '2 years', 'address' => 'Agra', 'description' => 'I sell well.',
            'document_path' => null, 'status' => 'new', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $jobs = preg_replace('/\s+/', ' ', strip_tags(Livewire::test(Careers::class)->html()));
        $this->assertStringContainsString('S.No Job Opening Actions 1 Sales Executive Sales · Aligarh Full-time · 3 LPA', $jobs);

        $page = Livewire::test(Careers::class)->call('switchTab', 'applications');
        $list = preg_replace('/\s+/', ' ', strip_tags($page->html()));
        $this->assertStringContainsString('S.No Application Actions 1 Asha Verma I sell well. Sales Executive ·asha@example.com ·9000000001 · Sun, 11 Oct 2026 · 10:00 AM · New', $list);

        $view = preg_replace('/\s+/', ' ', strip_tags($page->call('viewApplication', 1)->html()));
        $this->assertStringContainsString('Asha Verma Sales Executive · New', $view);
        $this->assertStringContainsString('Applied for Sales Executive Email asha@example.com Mobile 9000000001 Experience 2 years Qualification MBA Applied on 11 Oct 2026 · 10:00 AM Status New Address Agra About / Message I sell well. Resume Not attached Mark as Reviewed Close', $view);

        // Same toggle as before: the footer marks it reviewed and closes the view.
        $page->call('toggleReviewed', 1)->assertSet('viewingId', null);
        $this->assertSame('reviewed', DB::table('career_applications')->value('status'));
    }

    public function test_become_executive_applications_are_listed_as_website_enquiries(): void
    {
        Schema::create('executive_applications', function (Blueprint $t) {
            $t->id();
            foreach (['full_name', 'email', 'mobile', 'qualification', 'document_path', 'status'] as $c) {
                $t->string($c)->nullable();
            }
            $t->text('address')->nullable();
            $t->text('description')->nullable();
            $t->text('admin_remark')->nullable();
            $t->timestamps();
        });
        // The status save tells the super admins (ActivityNotifier), which reads users.
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            foreach (['name', 'email', 'role'] as $c) {
                $t->string($c)->nullable();
            }
            $t->timestamps();
        });
        DB::table('executive_applications')->insert([
            'full_name' => 'Rohit Singh', 'email' => 'rohit@example.com', 'mobile' => '9000000002', 'qualification' => 'B.Com',
            'address' => 'Mathura', 'description' => 'I know many schools.', 'status' => 'contacted',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $page = Livewire::test(BecomeExecutive::class);
        $list = preg_replace('/\s+/', ' ', strip_tags($page->html()));
        $this->assertStringContainsString('S.No Application Actions 1 Rohit Singh I know many schools. B.Com· rohit@example.com· 9000000002· Sun, 11 Oct 2026 · 10:00 AM · Contacted', $list);
        $this->assertStringContainsString('text-blue-600">Contacted', $page->html());

        $view = preg_replace('/\s+/', ' ', strip_tags($page->call('viewApplication', 1)->html()));
        $this->assertStringContainsString('Rohit Singh Partner / Executive application · Contacted', $view);
        $this->assertStringContainsString('Email rohit@example.com Mobile 9000000002 Qualification B.Com Applied on 11 Oct 2026 · 10:00 AM Status Contacted Address Mathura About / Message I know many schools. Document Not attached', $view);

        // Same status + remark save as before.
        $page->set('editStatus', 'approved')->set('editRemark', 'Good fit')->call('saveStatus')->assertSet('viewingId', null);
        $row = DB::table('executive_applications')->first();
        $this->assertSame(['approved', 'Good fit'], [$row->status, $row->admin_remark]);
    }

    public function test_documents_are_listed_as_the_school_panels_documents_without_the_file_name(): void
    {
        Storage::fake('s3');
        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->timestamps();
        });
        Schema::create('super_admin_documents', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->text('description')->nullable();
            foreach (['file_path', 'file_name', 'mime_type'] as $c) {
                $t->string($c)->nullable();
            }
            $t->unsignedBigInteger('file_size')->nullable();
            $t->string('audience_scope')->default('all');
            $t->unsignedBigInteger('uploaded_by')->nullable();
            $t->timestamps();
        });
        Schema::create('super_admin_document_organization', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('document_id');
            $t->unsignedBigInteger('organization_id');
            $t->timestamps();
        });
        DB::table('super_admin_documents')->insert([
            'title' => 'Testing document', 'description' => 'Testing Document', 'file_path' => 'super/t.pdf',
            'file_name' => 'भूलेख 71.pdf', 'file_size' => 1468006, 'audience_scope' => 'all',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $page = Livewire::test(Documents::class);
        $html = $page->html();
        $text = preg_replace('/\s+/', ' ', strip_tags($html));

        $this->assertStringContainsString('S.No Document Description Sent To Added On Actions 1 PDF Testing document 1.4 MB Testing Document All schools 11 Oct 2026', $text);
        $this->assertStringNotContainsString('भूलेख 71.pdf', $html);                   // the file's own name is not listed
        $this->assertStringContainsString('Total: <strong class="text-gray-800">1</strong>', $html);
        $this->assertStringContainsString('Add Doc', $html);
        foreach (['downloadDocument(1)', 'edit(1)', 'confirmDelete(1)'] as $action) {
            $this->assertStringContainsString('wire:click="' . $action . '"', $html);
        }

        // The form keeps who gets it, now as plain radio buttons.
        $form = $page->call('openCreate')->html();
        $this->assertSame(3, substr_count($form, 'name="documentAudience"'));
        $this->assertStringContainsString("wire:click=\"\$set('audienceScope', 'selected')\"", $form);
    }

    public function test_faqs_are_listed_as_the_website_enquiries(): void
    {
        Schema::create('faqs', function (Blueprint $t) {
            $t->id();
            $t->string('category')->default('General');
            $t->string('question');
            $t->text('answer')->nullable();
            $t->timestamps();
        });
        DB::table('faqs')->insert([
            'category' => 'Payments', 'question' => 'Can parents pay fees online?', 'answer' => 'Yes, from the parent app.',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $html = Livewire::test(Faqs::class)->html();
        $text = preg_replace('/\s+/', ' ', strip_tags($html));

        $this->assertStringContainsString('S.No FAQ Actions 1 Can parents pay fees online? Yes, from the parent app. Payments · Sun, 11 Oct 2026 · 10:00 AM', $text);
        $this->assertStringContainsString('wire:click="openEdit(1)"', $html);
        $this->assertStringContainsString('wire:click="confirmDelete(1)"', $html);
    }

    public function test_about_app_view_is_a_plain_document_with_minimal_boxes(): void
    {
        Schema::create('about_apps', function (Blueprint $t) {
            $t->id();
            foreach (['heading', 'sub_heading', 'logo', 'company_name', 'company_cin', 'address'] as $c) {
                $t->string($c)->nullable();
            }
            foreach (['content', 'contact_details', 'core_team', 'social_media', 'documents'] as $c) {
                $t->json($c)->nullable();
            }
            $t->timestamps();
        });
        DB::table('about_apps')->insert([
            'heading' => 'About SuperLMS', 'company_name' => 'SuperLMS Pvt Ltd', 'address' => 'Aligarh, UP',
            'content' => json_encode([['title' => 'Who we are', 'description' => 'A school app.'], ['title' => 'What we do', 'description' => 'Everything a school needs.']]),
            'contact_details' => json_encode([['type' => 'Email', 'value' => 'hello@superlms.in']]),
            'core_team' => json_encode([['name' => 'Asha', 'position' => 'Founder', 'link' => 'https://example.com/asha']]),
            'social_media' => json_encode([['platform' => 'instagram', 'url' => 'https://instagram.com/superlms']]),
            'documents' => json_encode([['title' => 'Brochure', 'file_type' => 'pdf', 'file_path' => 'https://example.com/b.pdf', 'file_size' => 204800]]),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $html = Livewire::test(AboutApp::class)->html();
        $text = preg_replace('/\s+/', ' ', strip_tags($html));

        $this->assertStringContainsString('min-h-screen bg-white', $html);
        $this->assertStringContainsString('1.Who we are A school app. 2.What we do Everything a school needs.', $text);
        $this->assertStringContainsString('Contact Details Email hello@superlms.in Address Aligarh, UP Our Core Team 1 members Asha Founder View Profile ↗', $text);
        $this->assertStringContainsString('Follow Us On Social Media instagram', $text);
        $this->assertStringContainsString('Documents 1 file(s) pdf Brochure 200 KB', $text);
        // No gradient headers, shadows or hover lift left on the view.
        $view = substr($html, 0, strpos($html, 'Brochure'));
        foreach (['bg-gradient-to-r', 'shadow-md', 'hover:-translate-y'] as $gone) {
            $this->assertStringNotContainsString($gone, $view);
        }
    }
}

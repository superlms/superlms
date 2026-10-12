<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\AboutApp;
use App\Livewire\SuperAdmin\BecomeExecutive;
use App\Livewire\SuperAdmin\Careers;
use App\Livewire\SuperAdmin\PrivacyPolicy;
use App\Livewire\SuperAdmin\PushNotification;
use App\Livewire\SuperAdmin\TermOfUse;
use App\Livewire\SuperAdmin\TermsCondition;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin: Become an Executive and Careers without the header line and
 * the list's round icon (Careers: the Website Queries tab); a sent push
 * notification can be deleted (and taken out of the recipients' bells); the
 * Terms & Conditions, Terms of Use, Privacy Policy and About App edit pages
 * in a plain, minimal look with the same fields and saves.
 */
class SuperAdminEditPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('e', 32))]);
        Carbon::setTestNow('2026-10-12 12:00:00');

        // Saves tell the super admins (ActivityNotifier reads users) — never a real push.
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            foreach (['name', 'email', 'role'] as $c) {
                $t->string($c)->nullable();
            }
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->text('permissions')->nullable();
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
        $this->app->instance(\App\Services\FirebaseNotificationService::class, new class {
            public function sendToUser(...$a) {}
            public function notifyUserIds(...$a) { return false; }
        });

        $user = new User(['name' => 'Super', 'email' => 'super@example.com']);
        $user->id = 1;
        $user->role = 'super-admin';
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function text(string $html): string
    {
        return preg_replace('/\s+/', ' ', strip_tags($html));
    }

    public function test_become_executive_has_no_header_line_and_no_list_icon(): void
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
        DB::table('executive_applications')->insert([
            'full_name' => 'Rohit Singh', 'email' => 'rohit@example.com', 'description' => 'I know many schools.',
            'status' => 'new', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $html = Livewire::test(BecomeExecutive::class)->html();

        $this->assertStringNotContainsString('Partner applications submitted from the website.', $html);
        $this->assertStringNotContainsString('w-9 h-9 rounded-full bg-gray-100', $html);
        $this->assertStringContainsString('1 Rohit Singh I know many schools.', $this->text($html));
    }

    public function test_careers_has_no_header_line_and_no_icon_on_website_queries(): void
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
            'slug' => 'careers', 'metadata' => json_encode(['jobs' => [['role' => 'Sales Executive', 'location' => 'Aligarh']]]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('career_applications')->insert([
            'job_role' => 'Sales Executive', 'full_name' => 'Asha Verma', 'description' => 'I sell well.', 'status' => 'new',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $page = Livewire::test(Careers::class);
        $this->assertStringNotContainsString('Manage job openings and applications submitted from the website.', $page->html());
        // Job Openings keeps its icon (not asked to change).
        $this->assertStringContainsString('w-9 h-9 rounded-full bg-gray-100', $page->html());

        $queries = $page->call('switchTab', 'applications')->html();
        $this->assertStringNotContainsString('w-9 h-9 rounded-full bg-gray-100', $queries);
        $this->assertStringContainsString('1 Asha Verma I sell well.', $this->text($queries));
    }

    private function pushTables(): void
    {
        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
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
    }

    private function bell(int $user, string $title, string $body, string $at): void
    {
        DB::table('notifications')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(), 'type' => 'promo', 'notifiable_type' => User::class,
            'notifiable_id' => $user, 'data' => json_encode(['type' => 'promo', 'title' => $title, 'body' => $body]),
            'created_at' => $at, 'updated_at' => $at,
        ]);
    }

    public function test_a_sent_notification_can_be_deleted_with_its_bell_copies(): void
    {
        $this->pushTables();
        DB::table('push_notification_campaigns')->insert([
            ['id' => 1, 'title' => 'Exam week', 'body' => 'Papers start Monday.', 'audience_roles' => json_encode(['students']), 'web_count' => 2, 'created_at' => '2026-10-10 09:00:05', 'updated_at' => '2026-10-10 09:00:05'],
            ['id' => 2, 'title' => 'Holiday', 'body' => 'Closed Friday.', 'audience_roles' => json_encode(['students']), 'web_count' => 1, 'created_at' => '2026-10-11 09:00:00', 'updated_at' => '2026-10-11 09:00:00'],
        ]);
        $this->bell(31, 'Exam week', 'Papers start Monday.', '2026-10-10 09:00:04');
        $this->bell(32, 'Exam week', 'Papers start Monday.', '2026-10-10 09:00:04');
        $this->bell(31, 'Exam week', 'Papers start Monday.', '2026-09-01 09:00:00');   // an older send of the same text
        $this->bell(31, 'Holiday', 'Closed Friday.', '2026-10-11 08:59:59');

        $page = Livewire::test(PushNotification::class);
        $html = $page->html();
        $this->assertStringContainsString('wire:click="confirmDelete(1)" title="Delete"', $html);
        $this->assertStringContainsString('>Actions</th>', $html);

        $page->call('confirmDelete', 1)
            ->assertSet('deletingId', 1)
            ->assertSet('deleteFromBells', true)
            ->assertSee('Delete Notification')
            ->assertSee('Also remove it from the recipients')
            ->call('deleteCampaign')
            ->assertSet('deletingId', null);

        $this->assertSame([2], DB::table('push_notification_campaigns')->pluck('id')->all());
        $this->assertSame(2, DB::table('notifications')->count());   // the older send and Holiday stay
        $this->assertSame(0, DB::table('notifications')->where('created_at', '2026-10-10 09:00:04')->count());

        // From the view, and keeping the bells this time.
        $page->call('viewCampaign', 2)
            ->assertSeeHtml('wire:click="confirmDelete(2)"')
            ->call('confirmDelete', 2)
            ->set('deleteFromBells', false)
            ->call('deleteCampaign')
            ->assertSet('viewingId', null);
        $this->assertSame(0, DB::table('push_notification_campaigns')->count());
        $this->assertSame(2, DB::table('notifications')->count());

        // Cancel leaves it alone.
        DB::table('push_notification_campaigns')->insert(['id' => 3, 'title' => 'X', 'body' => 'Y', 'created_at' => now(), 'updated_at' => now()]);
        $page->call('confirmDelete', 3)->call('cancelDelete')->assertSet('deletingId', null);
        $this->assertSame(1, DB::table('push_notification_campaigns')->count());
    }

    public function test_the_policy_edit_pages_are_plain_and_still_save(): void
    {
        $pages = [
            [TermsCondition::class, 'term_and_conditions', 'Edit Terms & Conditions'],
            [TermOfUse::class,      'term_of_uses',        'Edit Terms of Use'],
            [PrivacyPolicy::class,  'privacy_policies',    'Edit Privacy Policy'],
        ];

        foreach ($pages as [$component, $table, $title]) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                foreach (['platform_logo', 'platform_name', 'company_name', 'company_cin'] as $c) {
                    $t->string($c)->nullable();
                }
                $t->json('metadata')->nullable();
                $t->date('last_updated')->nullable();
                $t->timestamps();
            });
            DB::table($table)->insert([
                'metadata' => json_encode(['sections' => [['head' => 'First', 'desc' => 'One.'], ['head' => 'Second', 'desc' => 'Two.']]]),
                'last_updated' => '2026-10-01', 'created_at' => now(), 'updated_at' => now(),
            ]);

            $page = Livewire::test($component)->call('switchTab', 'edit');
            $html = $page->html();
            $text = $this->text($html);

            $this->assertStringContainsString($title . ' 2 sections · last updated becomes 12 Oct 2026 when you save Cancel Save', html_entity_decode($text), $component);
            $this->assertStringContainsString('class="min-h-screen bg-white"', $html);
            $this->assertStringContainsString('wire:model="sections.1.head"', $html);
            $this->assertStringContainsString('wire:click="confirmRemoveSection(1)"', $html);
            $this->assertStringContainsString('Add section', $text);
            foreach (['bg-gradient-to-r', 'rounded-2xl border border-gray-200 shadow-sm', 'border-dashed'] as $gone) {
                $this->assertStringNotContainsString($gone, $html, $component);
            }

            // Same adds, removes and save.
            $page->call('addSection')
                ->set('sections.2.head', 'Third')->set('sections.2.desc', 'Three.')
                ->call('confirmRemoveSection', 0)->call('executeRemoveSection')
                ->call('save')
                ->assertSet('activeTab', 'view');
            $saved = json_decode(DB::table($table)->value('metadata'), true)['sections'];
            $this->assertSame(['Second', 'Third'], array_column($saved, 'head'), $component);
            $this->assertSame('2026-10-12', substr(DB::table($table)->value('last_updated'), 0, 10));

            // Cancel goes back to the view.
            Livewire::test($component)->call('switchTab', 'edit')->call('switchTab', 'view')->assertSet('activeTab', 'view');
        }
    }

    public function test_the_about_app_edit_page_is_plain_and_still_saves(): void
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
            'content' => json_encode([['title' => 'Who we are', 'description' => 'A school app.']]),
            'contact_details' => json_encode([['type' => 'Email', 'value' => 'hello@superlms.in']]),
            'core_team' => json_encode([['name' => 'Asha', 'position' => 'Founder', 'description' => 'Started it.']]),
            'social_media' => json_encode([['platform' => 'instagram', 'url' => 'https://instagram.com/superlms']]),
            'documents' => json_encode([['title' => 'Brochure', 'file_type' => 'pdf', 'file_path' => 'https://example.com/b.pdf', 'file_size' => 204800]]),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $page = Livewire::test(AboutApp::class)->call('setTab', 'edit');
        $html = $page->html();
        $text = $this->text($html);

        foreach (['Edit About App Everything on this page is saved together with Save Cancel Save', 'Basic Information',
                  'Content Sections 1 section', 'Add section', 'Contact Details Add contact Email hello@superlms.in',
                  'Core Team Add member Asha Founder · Started it.', 'Social Media Add link instagram https://instagram.com/superlms',
                  'Documents PDF, DOC or DOCX · up to 2 MB each Add document Brochure PDF · 200 KB'] as $seen) {
            $this->assertStringContainsString($seen, $text);
        }
        foreach (['wire:model.defer="heading"', 'wire:model.defer="company_cin"', 'wire:model="logo"', 'wire:model="content.0.title"',
                  'wire:click="openContactModal(0)"', 'wire:click="removeContact(0)"', 'wire:click="openTeamModal(0)"',
                  'wire:click="removeTeamMember(0)"', 'wire:click="openSocialModal(0)"', 'wire:click="removeSocialMedia(0)"',
                  'wire:click="openDocumentModal(0)"', 'wire:click="removeDocument(0)"', 'wire:click="setTab(\'view\')"'] as $wire) {
            $this->assertStringContainsString($wire, $html);
        }
        $edit = substr($html, strpos($html, 'Edit About App'));
        foreach (['bg-gradient-to-r', 'shadow-md', 'rounded-2xl', 'border-dashed'] as $gone) {
            $this->assertStringNotContainsString($gone, substr($edit, 0, strpos($edit, 'DELETE') ?: strlen($edit)));
        }

        // Same save.
        $page->set('heading', 'About Us')->call('addContentSection')
            ->set('content.1.title', 'What we do')->set('content.1.description', 'Everything.')
            ->call('save');
        $row = DB::table('about_apps')->first();
        $this->assertSame('About Us', $row->heading);
        $this->assertSame(['Who we are', 'What we do'], array_column(json_decode($row->content, true), 'title'));
    }
}

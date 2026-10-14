<?php

namespace Tests\Feature;

use App\Livewire\Admin\AboutApp;
use App\Livewire\Admin\PrivacyPolicy;
use App\Livewire\Admin\RulesAndRegulation;
use App\Livewire\Admin\TermOfUse;
use App\Livewire\Admin\TermsAndCondition;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * School admin: Terms of Use, Terms & Conditions, Privacy Policy, About App
 * and Rules & Regulations in the Super Admin pages' look (2026-10-14) — the
 * name and a plain "Last updated" in the header, no logo, and white cards on
 * the grey page with a quiet S.No header row; Rules keeps its tabs under the
 * heading, its fields, adds, removes and save.
 */
class AdminPolicyPagesLookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('a', 32))]);
        Carbon::setTestNow('2026-10-14 12:00:00');

        $user = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $user->id = 7;
        $user->organization_id = 3;
        $this->actingAs($user);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function text(string $html): string
    {
        return html_entity_decode(preg_replace('/\s+/', ' ', strip_tags($html)));
    }

    private function policyTable(string $table): void
    {
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
            'company_name' => 'Super Learnings Pvt Ltd', 'company_cin' => 'U80904UP2023',
            'metadata' => json_encode(['sections' => [['head' => 'First', 'desc' => 'One.'], ['head' => 'Second', 'desc' => 'Two.']]]),
            'last_updated' => '2026-10-01', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_the_policy_pages_read_as_the_super_admin_view(): void
    {
        Schema::create('about_apps', function (Blueprint $t) {
            $t->id();
            $t->string('logo')->nullable();
            $t->timestamps();
        });

        $pages = [
            [PrivacyPolicy::class,     'privacy_policies',    'Privacy Policy Last updated 01 Oct 2026', 'Privacy Policy 2 sections'],
            [TermOfUse::class,         'term_of_uses',        'Terms Of Use Last updated 01 Oct 2026', 'Terms of Use 2 sections'],
            [TermsAndCondition::class, 'term_and_conditions', 'Terms & Conditions Super Learnings Pvt Ltd · CIN: U80904UP2023 Last updated 01 Oct 2026', 'Terms & Conditions 2 sections'],
        ];

        foreach ($pages as [$component, $table, $header, $card]) {
            $this->policyTable($table);
            $html = Livewire::test($component)->html();
            $text = $this->text($html);

            $this->assertStringContainsString($header, $text, $component);
            $this->assertStringContainsString($card . ' S.No Section 1 First One. 2 Second Two.', $text, $component);
            $this->assertStringContainsString('class="min-h-screen bg-gray-50"', $html);
            $this->assertStringContainsString('bg-white rounded-xl border border-gray-200 shadow-sm', $html);
            $this->assertStringContainsString('<table class="w-full table-fixed min-w-0!">', $html);
            foreach (['bg-gradient-to-r', 'Platform Logo', 'rounded-full border border-indigo-100', 'Introduction'] as $gone) {
                $this->assertStringNotContainsString($gone, $html, $component);
            }
        }
    }

    public function test_about_app_reads_as_the_super_admin_view(): void
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
            'heading' => 'About SuperLMS', 'sub_heading' => 'One app for the school', 'address' => 'Aligarh, UP',
            'content' => json_encode([['title' => 'Who we are', 'description' => 'A school app.']]),
            'contact_details' => json_encode([['type' => 'Email', 'value' => 'hello@superlms.in'], ['type' => 'Phone', 'value' => '+91 98765 43210']]),
            'core_team' => json_encode([['name' => 'Asha', 'position' => 'Founder', 'url' => 'https://example.com/asha']]),
            'social_media' => json_encode([['name' => 'instagram', 'url' => 'https://instagram.com/superlms']]),
            'documents' => json_encode([['title' => 'Brochure', 'file_type' => 'pdf', 'file_path' => 'https://example.com/b.pdf', 'file_size' => 204800]]),
            'created_at' => now(), 'updated_at' => '2026-10-10 09:00:00',
        ]);

        $html = Livewire::test(AboutApp::class)->html();
        $text = $this->text($html);

        foreach (['About SuperLMS One app for the school Updated 10 Oct 2026',
                  'Content 1 section S.No Section 1 Who we are A school app.',
                  'Contact Details 3 entries Type Detail Email hello@superlms.in Phone +91 98765 43210 Address Aligarh, UP',
                  'Our Core Team 1 members S.No Member Position Profile 1 A Asha Founder View Profile ↗',
                  'Follow Us On Social Media 1 links S.No Platform Link 1 instagram https://instagram.com/superlms',
                  'Documents 1 file(s) S.No Document Size Actions 1 pdf Brochure 200 KB'] as $seen) {
            $this->assertStringContainsString($seen, $text);
        }
        // Same links as before: write / call, the member's url, the file.
        foreach (['href="mailto:hello@superlms.in"', 'href="tel:+91 98765 43210"', 'href="https://example.com/asha"', 'href="https://example.com/b.pdf"'] as $link) {
            $this->assertStringContainsString($link, $html);
        }
        foreach (['bg-gradient-to-r', 'Platform Logo', 'hover:-translate-y', '📍'] as $gone) {
            $this->assertStringNotContainsString($gone, $html);
        }
    }

    public function test_rules_read_and_edit_as_the_super_admin_pages(): void
    {
        Schema::create('rules_and_regulations', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->text('content')->nullable();
            $t->timestamps();
        });
        DB::table('rules_and_regulations')->insert([
            'organization_id' => 3,
            'content' => json_encode([
                'sections' => [['head' => 'Attendance', 'desc' => 'Be on time.'], ['head' => 'Uniform', 'desc' => 'Wear it daily.']],
                'additional_info' => [['key' => 'Fees', 'value' => 'Paid monthly'], ['key' => '', 'value' => 'skipped']],
                'files' => [['title' => 'Handbook', 'file_path' => 'https://example.com/h.pdf', 'file_type' => 'pdf', 'file_size' => 3145728]],
                'last_updated' => '2026-10-05 10:00:00',
            ]),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $page = Livewire::test(RulesAndRegulation::class);
        $text = $this->text($page->html());
        foreach (['Rules & Regulations 2 rules Last updated 05 Oct 2026 Edit View Rules & Regulations Edit Rules & Regulations',
                  'Rules & Regulations 2 sections S.No Section 1 Attendance Be on time. 2 Uniform Wear it daily.',
                  'Additional Information 1 entry Key Value Fees Paid monthly',
                  'Attached Documents 1 file(s) S.No Document Size Actions 1 pdf Handbook 3 MB · Large'] as $seen) {
            $this->assertStringContainsString($seen, $text);
        }
        $this->assertStringNotContainsString('skipped', $text);
        $this->assertStringNotContainsString('bg-gradient-to-r', $page->html());

        // Edit: Cancel / Save in the header, the same fields, adds, removes and save.
        $page->call('showTab', 'edit');
        $html = $page->html();
        $text = $this->text($html);
        foreach (['Rules & Regulations 2 sections · published for your school with Save Cancel Save',
                  'Rules & Regulation Sections 2 sections S.No Heading & Text Actions',
                  'Additional Information Add info S.No Key Value Actions',
                  'Attached Documents PDF · up to 2 MB each Add document S.No Document Type · Size / File Actions 1 Handbook PDF · 3 MB · Large'] as $seen) {
            $this->assertStringContainsString($seen, $text);
        }
        foreach (['wire:model="sections.1.head"', 'wire:model="sections.1.desc"', 'wire:click="removeSection(1)"', 'wire:click="addSection"',
                  'wire:model="additionalInfo.0.key"', 'wire:click="removeAdditionalInfo(0)"', 'wire:click="addAdditionalInfo"',
                  'wire:click="addFileField"', 'wire:click="removeExistingFile(0)"', 'wire:click="saveContent"', "wire:click=\"showTab('view')\""] as $wire) {
            $this->assertStringContainsString($wire, $html);
        }

        $page->call('addSection')->set('sections.2.head', 'Library')->set('sections.2.desc', 'Return books in a week.')
            ->call('removeSection', 0)
            ->call('saveContent')
            ->assertSet('activeTab', 'view');
        $saved = json_decode(DB::table('rules_and_regulations')->value('content'), true);
        $this->assertSame(['Uniform', 'Library'], array_column($saved['sections'], 'head'));
        $this->assertSame('Handbook', $saved['files'][0]['title']);

        // Cancel goes back to the view.
        Livewire::test(RulesAndRegulation::class)->call('showTab', 'edit')->call('showTab', 'view')->assertSet('activeTab', 'view');
    }
}

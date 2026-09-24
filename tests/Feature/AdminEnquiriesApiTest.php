<?php

namespace Tests\Feature;

use App\Http\Controllers\v1\AdminContentController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The admin app's Enquiries are the panel's: students', teachers' and the
 * school website's, ten to a page when a page is asked for, a reply of at
 * least five characters, and no reply to a website enquiry.
 */
class AdminEnquiriesApiTest extends TestCase
{
    private int $org = 14;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->timestamps();
        });
        foreach (['contact_admin_students' => 'student_query', 'contact_admin_teachers' => 'teacher_query'] as $table => $col) {
            Schema::create($table, function (Blueprint $t) use ($col) {
                $t->id(); $t->unsignedBigInteger('student_detail_id')->nullable(); $t->unsignedBigInteger('teacher_detail_id')->nullable();
                $t->unsignedBigInteger('user_id')->nullable(); $t->unsignedBigInteger('organization_id');
                $t->string('topic')->nullable(); $t->text($col)->nullable(); $t->string('image')->nullable();
                $t->text('admin_text')->nullable(); $t->boolean('admin_reply')->default(false); $t->timestamps();
            });
        }
        Schema::create('school_website_enquiries', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('name')->nullable(); $t->string('email')->nullable();
            $t->string('phone')->nullable(); $t->string('subject')->nullable(); $t->text('message')->nullable(); $t->timestamps();
        });

        DB::table('organizations')->insert(['id' => $this->org, 'name' => 'DPS']);
        $u = DB::table('users')->insertGetId(['name' => 'Aarav', 'email' => 'aarav@x.in', 'organization_id' => $this->org]);
        for ($i = 1; $i <= 12; $i++) {
            DB::table('contact_admin_students')->insert([
                'user_id' => $u, 'organization_id' => $this->org, 'topic' => "Topic $i", 'student_query' => "Query $i",
                'created_at' => now()->subMinutes(20 - $i), 'updated_at' => now(),
            ]);
        }
        DB::table('school_website_enquiries')->insert([
            ['organization_id' => $this->org, 'name' => 'Ravi', 'email' => 'ravi@x.in', 'phone' => '9876543210', 'subject' => 'Admission', 'message' => 'Class 5 seats?', 'created_at' => now(), 'updated_at' => now()],
            ['organization_id' => $this->org, 'name' => 'Meena', 'email' => null, 'phone' => '9123456780', 'subject' => 'Fees', 'message' => 'Fee for class 1', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $admin = new User(['name' => 'Head', 'email' => 'head@x.in']);
        $admin->id = 99;
        $admin->organization_id = $this->org;
        $admin->role = 'admin';
        $this->actingAs($admin);
    }

    private function api(): AdminContentController
    {
        return app(AdminContentController::class);
    }

    public function test_the_three_tabs_and_ten_to_a_page(): void
    {
        $page = $this->api()->enquiries(new Request(['tab' => 'student', 'page' => 1]))->getData(true)['data'];
        $this->assertCount(10, $page['enquiries']);
        $this->assertSame('Topic 12', $page['enquiries'][0]['topic']);
        $this->assertSame(2, $page['pagination']['last_page']);
        $this->assertSame(['teacher' => 0, 'student' => 12, 'website' => 2], $page['tab_totals']);
        $this->assertSame('DPS', $page['enquiries'][0]['organization']);

        // Without a page, as older builds ask: all of them.
        $all = $this->api()->enquiries(new Request(['tab' => 'student']))->getData(true)['data'];
        $this->assertCount(12, $all['enquiries']);
        $this->assertNull($all['pagination']);

        $web = $this->api()->enquiries(new Request(['tab' => 'website', 'search' => '9123']))->getData(true)['data'];
        $this->assertSame(['Fees'], array_column($web['enquiries'], 'topic'));
        $this->assertSame('Meena', $web['enquiries'][0]['user_name']);
        $this->assertSame(0, $web['stats']['pending']);
    }

    public function test_a_reply_is_five_characters_or_more_and_a_website_enquiry_has_none(): void
    {
        $id = DB::table('contact_admin_students')->value('id');

        $short = $this->api()->replyEnquiry(new Request(['admin_text' => 'ok']), 'student', $id);
        $this->assertSame(422, $short->status());

        $ok = $this->api()->replyEnquiry(new Request(['admin_text' => 'Please see the office.']), 'student', $id);
        $this->assertSame(200, $ok->status());
        $d = $ok->getData(true)['data'];
        $this->assertTrue($d['replied']);
        $this->assertNotNull($d['replied_at']);

        $webId = DB::table('school_website_enquiries')->value('id');
        $this->assertSame(422, $this->api()->replyEnquiry(new Request(['admin_text' => 'Thanks for asking']), 'website', $webId)->status());

        $this->assertSame(200, $this->api()->deleteEnquiry('website', $webId)->status());
        $this->assertSame(1, DB::table('school_website_enquiries')->count());
    }
}

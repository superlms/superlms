<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\WelcomeWhatsApp;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Send Credentials (2026-10-14): on a student's or teacher's view — school
 * admin and Super Admin — a button left of Close sends the WhatsApp welcome
 * again: the same template, a fresh "View details" link, the password left as
 * it is; and says why when it cannot go.
 */
class SendCredentialsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'app.key' => 'base64:' . base64_encode(str_repeat('s', 32)),
            'services.whatsapp.enabled' => true,
            'services.whatsapp.token' => 'test-token',
            'services.whatsapp.phone_number_id' => '1294658720402506',
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            foreach (['name', 'email', 'username', 'password', 'role', 'mobile_number'] as $c) {
                $t->string($c)->nullable();
            }
            $t->text('password_plain')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('organizations', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('logo')->nullable(); $t->timestamps(); });
        Schema::create('standards', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); });
        Schema::create('sections', function (Blueprint $t) { $t->id(); $t->string('name'); $t->timestamps(); });
        Schema::create('student_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('organization_id');
            $t->unsignedBigInteger('standard_id')->nullable();
            $t->unsignedBigInteger('section_id')->nullable();
            $t->string('full_name')->nullable();
            $t->string('admission_no')->nullable();
            $t->string('phone')->nullable();
            $t->timestamps();
        });
        Schema::create('teacher_details', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });
        Schema::create('account_setup_links', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->char('token_hash', 64)->unique();
            $t->string('password_hash')->nullable();
            $t->timestamp('expires_at');
            $t->timestamp('opened_at')->nullable();
            $t->timestamps();
        });

        DB::table('organizations')->insert(['id' => 3, 'name' => 'Alpha School']);
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Admin', 'role' => 'admin', 'organization_id' => 3, 'username' => null, 'mobile_number' => null, 'password' => 'x'],
            ['id' => 10, 'name' => 'Asha', 'role' => 'user', 'organization_id' => 3, 'username' => null, 'mobile_number' => '9876543210', 'password' => 'hash-asha'],
            ['id' => 20, 'name' => 'Ravi Sir', 'role' => 'teacher', 'organization_id' => 3, 'username' => 'T-RAVI', 'mobile_number' => '9123456780', 'password' => 'hash-ravi'],
            ['id' => 30, 'name' => 'Other', 'role' => 'user', 'organization_id' => 4, 'username' => null, 'mobile_number' => '9000000001', 'password' => 'x'],
        ]);
        DB::table('student_details')->insert([
            ['user_id' => 10, 'organization_id' => 3, 'full_name' => 'Asha', 'admission_no' => 'ADM-1', 'phone' => null],
            ['user_id' => 30, 'organization_id' => 4, 'full_name' => 'Other', 'admission_no' => 'ADM-9', 'phone' => null],
        ]);
        DB::table('teacher_details')->insert(['user_id' => 20, 'organization_id' => 3]);
    }

    private function sentTo(string $number, string $template): bool
    {
        $this->app->terminate();   // the message goes after the response

        return collect(Http::recorded())->contains(fn ($pair) => $pair[0]['to'] === $number && $pair[0]['template']['name'] === $template);
    }

    public function test_a_student_and_a_teacher_get_their_welcome_again(): void
    {
        $student = WelcomeWhatsApp::resend(User::find(10), 'student');
        $teacher = WelcomeWhatsApp::resend(User::find(20), 'teacher');
        $this->assertSame(['ok' => true, 'message' => 'Credentials sent on WhatsApp to +919876543210.'], $student);
        $this->assertTrue($teacher['ok']);

        // One request's end: both go, each once.
        $this->assertTrue($this->sentTo('919876543210', 'admission_confirmed'));
        $this->assertTrue(collect(Http::recorded())->contains(fn ($p) => $p[0]['to'] === '919123456780' && $p[0]['template']['name'] === 'teacher_joining_confirmed'));
        $this->assertCount(2, Http::recorded());

        // A fresh link each; the password is left as it is.
        $this->assertSame(2, DB::table('account_setup_links')->count());
        $this->assertSame('hash-asha', DB::table('users')->where('id', 10)->value('password'));
    }

    public function test_it_says_why_when_it_cannot_go(): void
    {
        DB::table('users')->where('id', 20)->update(['username' => null]);
        $this->assertSame('This teacher has no username yet, so there is nothing to send.', WelcomeWhatsApp::resend(User::find(20), 'teacher')['message']);

        DB::table('users')->where('id', 10)->update(['mobile_number' => '12345']);
        $this->assertSame('No valid mobile number to send the credentials to.', WelcomeWhatsApp::resend(User::find(10), 'student')['message']);

        config(['services.whatsapp.enabled' => false]);
        $this->assertFalse(WelcomeWhatsApp::resend(User::find(10), 'student')['ok']);

        $this->app->terminate();
        Http::assertNothingSent();
    }

    public function test_the_school_panel_sends_only_to_its_own_people(): void
    {
        $this->actingAs(User::find(1));

        $own = new \App\Livewire\Admin\Student();
        $own->viewData = ['user' => User::find(10)];
        $own->sendCredentials();
        $this->assertTrue($this->sentTo('919876543210', 'admission_confirmed'));

        // Another school's student: nothing goes.
        $other = new \App\Livewire\Admin\Student();
        $other->viewData = ['user' => User::find(30)];
        $other->sendCredentials();
        $this->assertFalse($this->sentTo('919000000001', 'admission_confirmed'));

        $teacher = new \App\Livewire\Admin\Teacher();
        $teacher->viewData = ['user' => User::find(20)];
        $teacher->sendCredentials();
        $this->assertTrue($this->sentTo('919123456780', 'teacher_joining_confirmed'));
    }

    public function test_the_super_admin_panel_sends_too(): void
    {
        $this->actingAs(User::find(1));

        $student = new \App\Livewire\SuperAdmin\Student();
        $student->viewData = ['user' => User::find(30)];
        $student->sendCredentials();
        $this->assertTrue($this->sentTo('919000000001', 'admission_confirmed'));

        $teacher = new \App\Livewire\SuperAdmin\Teacher();
        $teacher->viewData = ['user' => User::find(20)];
        $teacher->sendCredentials();
        $this->assertTrue($this->sentTo('919123456780', 'teacher_joining_confirmed'));
    }

    public function test_the_button_sits_left_of_close_on_every_view(): void
    {
        foreach (['admin/student', 'admin/teacher', 'super-admin/student', 'super-admin/teacher'] as $view) {
            $blade  = file_get_contents(resource_path("views/livewire/{$view}.blade.php"));
            $send   = strpos($blade, 'wire:click="sendCredentials"');
            $footer = strrpos(substr($blade, 0, $send), 'border-t border-gray-200 flex items-center justify-between');
            $close  = strpos($blade, '>Close</button>', $send);

            $this->assertNotFalse($send, $view);
            $this->assertNotFalse($footer, $view);                         // in the view's footer …
            $this->assertLessThan(1500, $send - $footer, $view);
            $this->assertNotFalse($close, $view);                          // … right before Close
            $this->assertLessThan(3000, $close - $send, $view);
            $this->assertStringContainsString('Send Credentials', substr($blade, $send, $close - $send), $view);
            $this->assertStringContainsString('wire:confirm="Send the login details again on WhatsApp?"', substr($blade, $send, 200), $view);
        }
    }
}

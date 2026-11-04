<?php

namespace Tests\Feature;

use App\Livewire\SuperAdmin\Profile;
use App\Models\User;
use App\Services\StoreInvoices;
use App\Support\OtpNumbers;
use App\Support\Platform;
use App\Support\SuperAdminLogins;
use App\Support\SvgSanitizer;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Super Admin → Profile: the numbers the sign-in code goes to (one primary),
 * the platform logo as an image or a cleaned SVG, and the platform's details.
 */
class SuperAdminProfileTest extends TestCase
{
    private User $boss;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:' . base64_encode(str_repeat('k', 32))]);

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->string('password')->nullable(); $t->string('mobile_number')->nullable();
            $t->string('alternative_mobile')->nullable(); $t->string('image')->nullable(); $t->timestamps();
        });
        (require base_path('database/migrations/2026_11_01_160000_create_user_otp_numbers_table.php'))->up();
        (require base_path('database/migrations/2026_11_01_160100_create_platform_settings_table.php'))->up();
        (require base_path('database/migrations/2026_11_04_120000_create_super_admin_logins_table.php'))->up();
        app()->forgetInstance('otp_numbers.tracked');

        // Platform keeps a request's settings in a static; a test starts clean.
        \Illuminate\Support\Facades\Cache::forget('platform_settings');
        \Closure::bind(fn () => Platform::$settings = null, null, Platform::class)();

        $this->boss = User::forceCreate([
            'name' => 'Main', 'email' => 'superlms.india@gmail.com', 'role' => 'super-admin', 'mobile_number' => '8864985914',
        ]);
    }

    private function phones(): array
    {
        return array_map(fn ($n) => ($n['primary'] ? '*' : '') . $n['phone'], OtpNumbers::forUser($this->boss->fresh()));
    }

    public function test_the_migration_turns_the_alternative_mobile_into_a_second_number(): void
    {
        Schema::drop('user_otp_numbers');
        $this->boss->forceFill(['alternative_mobile' => '9368388050'])->save();

        (require base_path('database/migrations/2026_11_01_160000_create_user_otp_numbers_table.php'))->up();

        $this->assertSame(['*8864985914', '9368388050'], $this->phones());
    }

    public function test_numbers_are_added_edited_made_primary_and_deleted(): void
    {
        $this->assertNull(OtpNumbers::add($this->boss, '+91 93683 88050'));
        $this->assertNull(OtpNumbers::add($this->boss, '09811122233'));
        $this->assertSame(['*8864985914', '9368388050', '9811122233'], $this->phones());

        $second = OtpNumbers::forUser($this->boss->fresh())[1]['id'];

        // Making another number primary swaps the two; the code follows it.
        $this->assertNull(OtpNumbers::makePrimary($this->boss, $second));
        $this->assertSame('9368388050', OtpNumbers::primary($this->boss->fresh()));
        $this->assertSame(['*9368388050', '8864985914', '9811122233'], $this->phones());

        // Editing the primary changes the account's own number.
        $this->assertNull(OtpNumbers::update($this->boss, null, '9000011111'));
        $this->assertSame('9000011111', $this->boss->fresh()->mobile_number);

        // Deleting the primary hands it to the oldest other number.
        $this->assertNull(OtpNumbers::delete($this->boss, null));
        $this->assertSame(['*8864985914', '9811122233'], $this->phones());
    }

    public function test_every_wrong_number_says_what_is_wrong(): void
    {
        $this->assertSame('Enter a mobile number.', OtpNumbers::add($this->boss, '  '));
        $this->assertSame('Enter a 10-digit mobile number — 7 digits entered.', OtpNumbers::add($this->boss, '9368388'));
        $this->assertSame('An Indian mobile number starts with 6, 7, 8 or 9.', OtpNumbers::add($this->boss, '1234567890'));
        $this->assertStringContainsString('digits only', OtpNumbers::add($this->boss, '98765abc10'));
        $this->assertSame('+91 88649 85914 is already added.', OtpNumbers::add($this->boss, '8864985914'));

        foreach (['9000000001', '9000000002', '9000000003', '9000000004'] as $n) {
            OtpNumbers::add($this->boss, $n);
        }
        $this->assertStringStartsWith('Up to 5 numbers', OtpNumbers::add($this->boss, '9000000005'));
    }

    public function test_the_profile_page_manages_the_numbers(): void
    {
        $this->actingAs($this->boss);

        Livewire::test(Profile::class)
            ->assertSee('+91 88649 85914')
            ->assertSee('Primary · gets the OTP')
            ->set('newNumber', '93683')->call('addNumber')
            ->assertSee('Enter a 10-digit mobile number — 5 digits entered.')
            ->set('newNumber', '9368388050')->call('addNumber')
            ->assertHasNoErrors()
            ->assertSee('+91 93683 88050')
            ->assertSee('Make primary');
    }

    public function test_a_png_logo_is_stored_and_used_everywhere(): void
    {
        Storage::fake('s3');
        $this->actingAs($this->boss);

        $this->assertStringEndsWith('website-image/Group%2011525.png', str_replace(' ', '%20', Platform::logoUrl()));

        Livewire::test(Profile::class)
            ->set('logo', UploadedFile::fake()->image('logo.png', 120, 120))
            ->call('saveLogo')
            ->assertHasNoErrors();

        $this->assertTrue(Platform::hasCustomLogo());
        $this->assertStringContainsString('platform/logo/logo-', Platform::logoUrl());
        Storage::disk('s3')->assertExists(Platform::get('logo_path'));

        Platform::resetLogo();
        $this->assertFalse(Platform::hasCustomLogo());
    }

    public function test_an_svg_logo_is_cleaned_before_it_is_stored(): void
    {
        Storage::fake('s3');
        $this->actingAs($this->boss);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(2)</script>'
             . '<a href="javascript:alert(3)"><circle cx="5" cy="5" r="4" fill="#6f56fe"/></a></svg>';

        Livewire::test(Profile::class)
            ->set('logo', UploadedFile::fake()->createWithContent('logo.svg', $svg))
            ->call('saveLogo')
            ->assertHasNoErrors();

        $stored = Storage::disk('s3')->get(Platform::get('logo_path'));
        $this->assertStringContainsString('<circle', $stored);
        $this->assertStringNotContainsString('script', $stored);
        $this->assertStringNotContainsString('onload', $stored);
        $this->assertStringNotContainsString('javascript:', $stored);
    }

    public function test_a_wrong_logo_file_says_why(): void
    {
        $this->actingAs($this->boss);

        Livewire::test(Profile::class)
            ->set('logo', UploadedFile::fake()->create('logo.pdf', 10))
            ->assertSee('A .pdf file can')
            ->set('logo', UploadedFile::fake()->createWithContent('logo.png', 'not a picture'))
            ->assertSee('is not a picture that can be opened');

        $this->assertNull(SvgSanitizer::clean('<!DOCTYPE svg [<!ENTITY x SYSTEM "file:///etc/passwd">]><svg>&x;</svg>'));
        $this->assertNull(SvgSanitizer::clean('<html><body>hi</body></html>'));
    }

    public function test_platform_part_shows_details_and_website_data_to_the_main_super_admin_only(): void
    {
        $this->actingAs($this->boss);

        // One page, no tabs: the account, numbers, password and platform together.
        Livewire::test(Profile::class)
            ->assertSee('OTP Numbers')
            ->assertSee('Change Password')
            ->assertSee('Platform Logo')
            ->assertSee('Platform Details')
            ->assertSee('Website Data')
            ->assertSee('Contact enquiries');

        $sub = User::forceCreate(['name' => 'Sub', 'email' => 'sub@example.com', 'role' => 'sub-super-admin']);
        $this->actingAs($sub);

        Livewire::test(Profile::class)
            ->assertSee('OTP Numbers')
            ->assertSee('Change Password')
            ->assertSee('Login Activity')
            ->assertDontSee('Platform Details')
            ->assertDontSee('Platform Logo');
    }

    public function test_a_super_admin_sign_in_is_recorded_with_its_email_way_in_device_and_ip(): void
    {
        config(['services.otp.login_enabled' => true]);
        request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0 Safari/537.36');
        request()->server->set('REMOTE_ADDR', '203.0.113.9');

        event(new Login('superadmin', $this->boss, false));
        event(new Login('admin', $this->boss, false));   // a school's panel: not the Super Admin's

        $rows = DB::table('super_admin_logins')->get();
        $this->assertCount(1, $rows);
        $this->assertSame('superlms.india@gmail.com', $rows[0]->email);
        $this->assertSame('Main', $rows[0]->name);
        $this->assertSame('otp', $rows[0]->method);
        $this->assertSame('203.0.113.9', $rows[0]->ip);
        $this->assertSame(['Chrome', 'Windows', false], SuperAdminLogins::device($rows[0]->user_agent));
        $this->assertSame(['Safari', 'iPhone', true], SuperAdminLogins::device('Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 Version/18.0 Mobile/15E148 Safari/604.1'));
    }

    public function test_login_activity_lists_the_last_15_days_everyone_for_the_main_and_their_own_for_a_sub(): void
    {
        $sub = User::forceCreate(['name' => 'Rohit', 'email' => 'rohit@superlms.in', 'role' => 'sub-super-admin']);
        $row = fn (User $u, $at, $method = 'otp') => DB::table('super_admin_logins')->insert([
            'user_id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->role,
            'method' => $method, 'ip' => '10.0.0.1', 'user_agent' => 'Mozilla/5.0 (Linux; Android 14) Chrome/131.0 Mobile Safari/537.36', 'logged_in_at' => $at,
        ]);
        $row($this->boss, now()->subHours(2));
        $row($this->boss, now()->subDays(3), 'password');
        $row($sub, now()->subDay());
        DB::table('super_admin_logins')->insert(['name' => 'Old', 'email' => 'old@superlms.in', 'role' => 'super-admin', 'logged_in_at' => now()->subDays(20)]);

        $this->actingAs($this->boss);
        Livewire::test(Profile::class)
            ->assertSee('Login Activity')
            ->assertSee('3 logins')
            ->assertSee('rohit@superlms.in')
            ->assertSee('Password + OTP')
            ->assertSee('Chrome')
            ->assertDontSee('old@superlms.in');

        $this->actingAs($sub);
        $seen = SuperAdminLogins::recent($sub);
        $this->assertSame(1, $seen['total']);
        $this->assertSame(['rohit@superlms.in'], array_column($seen['rows'], 'email'));
        $this->assertCount(15, $seen['days']);
    }

    public function test_platform_details_are_checked_saved_and_fill_the_invoices_blanks(): void
    {
        $this->actingAs($this->boss);

        Livewire::test(Profile::class)
            ->assertSet('details.platform_name', config('app.name'))
            ->set('details.company_cin', 'U123')
            ->set('details.company_gstin', '09abcde1234f1z')
            ->set('details.company_mobile', '12345')
            ->call('saveDetails')
            ->assertSee('A CIN is 21 letters and digits')
            ->assertSee('A GSTIN is 15 letters and digits')
            ->set('details.company_name', 'SuperLMS Technologies Pvt Ltd')
            ->set('details.company_cin', 'u80301up2021ptc123456')
            ->set('details.company_gstin', '09ABCDE1234F1Z5')
            ->set('details.company_email', 'Hello@SuperLMS.in')
            ->set('details.company_mobile', '+91 88649 85914')
            ->set('details.company_website', 'superlms.in')
            ->set('details.company_address', 'Sector 62, Noida, UP 201309')
            ->call('saveDetails')
            ->assertHasNoErrors()
            ->assertSet('details.company_cin', 'U80301UP2021PTC123456')
            ->assertSet('details.company_mobile', '+91 88649 85914')
            ->assertSet('details.company_website', 'https://superlms.in');

        $this->assertSame('hello@superlms.in', Platform::get('company_email'));
        $this->assertSame('8864985914', Platform::get('company_mobile'));
        $this->assertSame('SuperLMS Technologies Pvt Ltd', Platform::get('company_name'));

        // Invoices: Store's own blanks come from here.
        $seller = StoreInvoices::seller();
        $this->assertSame('SuperLMS Technologies Pvt Ltd', $seller['name']);
        $this->assertSame('09ABCDE1234F1Z5', $seller['gstin']);
        $this->assertSame('Sector 62, Noida, UP 201309', $seller['address']);
        $this->assertSame('+91 88649 85914', $seller['phone']);

        // A sub super admin can't change them.
        $sub = User::forceCreate(['name' => 'Sub', 'email' => 'sub@example.com', 'role' => 'sub-super-admin']);
        $this->actingAs($sub);
        Livewire::test(Profile::class)->set('details.company_name', 'Hacked')->call('saveDetails');
        $this->assertSame('SuperLMS Technologies Pvt Ltd', Platform::get('company_name'));
    }
}

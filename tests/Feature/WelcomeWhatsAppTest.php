<?php

namespace Tests\Feature;

use App\Models\AccountSetupLink;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The WhatsApp welcome: the template message as Meta's Cloud API wants it,
 * and the page its button opens — the login and password while the link is
 * fresh and the password unchanged, and never after.
 */
class WelcomeWhatsAppTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:' . base64_encode(str_repeat('k', 32))]);

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('username')->nullable();
            $t->string('password')->nullable();
            $t->text('password_plain')->nullable();
            $t->string('role')->nullable();
            $t->string('mobile_number')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable();
            $t->timestamps();
        });
        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('logo')->nullable();
            $t->timestamps();
        });
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
        Schema::create('account_setup_links', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->char('token_hash', 64)->unique();
            $t->string('password_hash')->nullable();
            $t->timestamp('expires_at');
            $t->timestamp('opened_at')->nullable();
            $t->timestamps();
        });
    }

    public function test_an_indian_mobile_is_put_as_whatsapp_wants_it(): void
    {
        $this->assertSame('919876543210', WhatsAppService::normalizePhone('98765 43210'));
        $this->assertSame('919876543210', WhatsAppService::normalizePhone('+91-98765-43210'));
        $this->assertSame('919876543210', WhatsAppService::normalizePhone('09876543210'));
        $this->assertNull(WhatsAppService::normalizePhone('12345'));
        $this->assertNull(WhatsAppService::normalizePhone(null));
    }

    public function test_nothing_is_sent_until_it_is_configured(): void
    {
        config(['services.whatsapp.token' => null, 'services.whatsapp.phone_number_id' => null]);
        Http::fake();

        $this->assertFalse(WhatsAppService::sendTemplate('9876543210', 'admission_confirmed', ['A']));
        Http::assertNothingSent();
    }

    public function test_the_template_goes_with_its_body_and_the_button_link(): void
    {
        config([
            'services.whatsapp.enabled' => true,
            'services.whatsapp.token' => 'test-token',
            'services.whatsapp.phone_number_id' => '1294658720402506',
        ]);
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);

        $ok = WhatsAppService::sendTemplate('9876543210', 'admission_confirmed',
            ['Aarav Sharma', '5 - A', 'Delhi Public School', '26TDS100001'], 'tok123');

        $this->assertTrue($ok);
        Http::assertSent(function (HttpRequest $r) {
            $t = $r['template'];
            return str_contains($r->url(), '/1294658720402506/messages')
                && $r->hasHeader('Authorization', 'Bearer test-token')
                && $r['to'] === '919876543210'
                && $t['name'] === 'admission_confirmed'
                && $t['language']['code'] === 'en'
                && array_column($t['components'][0]['parameters'], 'text') === ['Aarav Sharma', '5 - A', 'Delhi Public School', '26TDS100001']
                && $t['components'][1]['sub_type'] === 'url'
                && $t['components'][1]['parameters'][0]['text'] === 'tok123';
        });
    }

    private function student(string $password): User
    {
        $org  = DB::table('organizations')->insertGetId(['name' => 'Delhi Public School']);
        $user = User::forceCreate([
            'name' => 'Aarav Sharma', 'role' => 'user', 'organization_id' => $org,
            'password' => Hash::make($password), 'password_plain' => Crypt::encryptString($password),
        ]);
        DB::table('student_details')->insert([
            'user_id' => $user->id, 'organization_id' => $org,
            'full_name' => 'Aarav Sharma', 'admission_no' => '26TDS100001',
        ]);

        return $user;
    }

    public function test_the_page_shows_the_login_and_password_while_the_link_is_fresh(): void
    {
        $user  = $this->student('Xk9#pQ2m');
        $token = AccountSetupLink::issue($user);

        $this->get('/account-setup/' . $token)
            ->assertOk()
            ->assertSee('26TDS100001')
            ->assertSee('Xk9#pQ2m', false)
            ->assertSee('Download Now')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        // As the approved template's button sends it: a literal {{1}} before the token.
        $this->get('/account-setup/%7B%7B1%7D%7D' . $token)->assertOk()->assertSee('Xk9#pQ2m', false);
        $this->get('/account-setup/{{1}}' . $token)->assertOk()->assertSee('26TDS100001');

        // Only the hash is kept.
        $this->assertDatabaseMissing('account_setup_links', ['token_hash' => $token]);
    }

    public function test_no_password_once_it_is_changed_or_the_link_has_expired(): void
    {
        $user  = $this->student('Xk9#pQ2m');
        $token = AccountSetupLink::issue($user);

        $user->forceFill(['password' => Hash::make('new-one')])->save();
        $this->get('/account-setup/' . $token)
            ->assertOk()->assertSee('26TDS100001')->assertDontSee('Xk9#pQ2m', false)
            ->assertSee('already been changed');

        $other = AccountSetupLink::issue($user->fresh());
        AccountSetupLink::where('token_hash', hash('sha256', $other))->update(['expires_at' => now()->subDay()]);
        $this->get('/account-setup/' . $other)->assertOk()->assertSee('expired');

        $this->get('/account-setup/' . str_repeat('x', 40))->assertNotFound();
    }
}

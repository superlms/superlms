<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Caddy's on-demand TLS asks /internal/tls-check before it gets a certificate
 * for a school's own domain: yes only for a published school website's.
 */
class TlsCheckTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('t', 32))]);

        Schema::create('organizations', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->timestamps();
        });
        Schema::create('school_infos', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->timestamps();
        });
        Schema::create('school_websites', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('organization_id'); $t->string('domain')->nullable();
            $t->boolean('status')->default(false); $t->timestamps();
        });

        DB::table('organizations')->insert([['id' => 1, 'name' => 'The Demo School'], ['id' => 2, 'name' => 'Draft School']]);
        DB::table('school_websites')->insert([
            ['organization_id' => 1, 'domain' => 'edyonelms.in', 'status' => true],
            ['organization_id' => 2, 'domain' => 'draft-school.in', 'status' => false],
        ]);
    }

    public function test_a_published_school_domain_gets_a_certificate_and_nothing_else_does(): void
    {
        $ask = fn (string $domain) => $this->get('/internal/tls-check?domain=' . urlencode($domain))->status();

        $this->assertSame(200, $ask('edyonelms.in'));
        $this->assertSame(200, $ask('www.edyonelms.in'));
        $this->assertSame(200, $ask('EdyoneLMS.in'));

        $this->assertSame(404, $ask('draft-school.in'));      // not published
        $this->assertSame(404, $ask('evil.example.com'));      // not a school's
        $this->assertSame(404, $ask(''));
        $this->assertSame(404, $ask('edyonelms.in/../x'));
        $this->assertSame(404, $this->get('/internal/tls-check')->status());
    }
}

<?php

namespace Tests\Feature;

use App\Http\Controllers\SchoolSiteController;
use App\Livewire\Admin\WebsiteData;
use App\Models\Organization;
use App\Models\SchoolWebsite;
use App\Models\User;
use App\Support\MapLink;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Website Data page by page (user's ask of 2026-10-16): it opens on the list
 * of the site's pages, a page opens on the blocks that page shows — text,
 * photos, PDFs, lists — and the home page's map is set from a Google Maps
 * link, the phone's location or the address.
 */
class WebsiteDataPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('w', 32))]);

        Schema::create('organizations', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('logo')->nullable();
            $t->string('mobile_number')->nullable();
            $t->string('address')->nullable();
            $t->string('education_board')->nullable();
            $t->timestamps();
        });
        Schema::create('school_infos', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id');
            $t->timestamps();
        });
        Schema::create('school_websites', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('organization_id')->unique();
            $t->string('domain')->nullable()->unique();
            $t->string('template')->default('kider');
            $t->json('theme')->nullable();
            $t->json('pages')->nullable();
            $t->json('content')->nullable();
            $t->boolean('status')->default(false);
            $t->timestamps();
        });
        DB::table('organizations')->insert([
            'id' => 1, 'name' => 'Sunrise Public School', 'education_board' => 'CBSE',
            'address' => 'Fatehabad Road, Agra', 'mobile_number' => '9457293157',
        ]);

        $admin = new User(['name' => 'Admin', 'email' => 'admin@example.com']);
        $admin->id = 901;
        $admin->role = 'admin';
        $admin->organization_id = 1;
        $this->actingAs($admin);
    }

    private function website(array $attrs = []): SchoolWebsite
    {
        return SchoolWebsite::create(array_merge(['organization_id' => 1, 'template' => 'classic'], $attrs));
    }

    private function render(string $path): array
    {
        $site = SchoolWebsite::first();
        $site->setRelation('organization', Organization::find(1)->setRelation('schoolInfo', null));
        $res = app(SchoolSiteController::class)->render($site, $path);

        return [$res->getStatusCode(), $res->getContent()];
    }

    private function text($component): string
    {
        return html_entity_decode(preg_replace('/\s+/', ' ', strip_tags($component->html())));
    }

    public function test_it_opens_on_the_sites_pages_under_its_menus(): void
    {
        $this->website([
            'pages'   => ['home', 'about', 'history', 'vision', 'gallery', 'disclosures', 'contact', 'query'],
            'content' => ['pages' => ['history' => ['heading' => 'History', 'body' => 'Founded in 1990.']]],
        ]);
        $off = count(SchoolWebsite::allPages()) - 8;

        $list = Livewire::test(WebsiteData::class)->assertSet('open', '');
        $text = $this->text($list);

        // Quick Links' tiles: School Details first, then the site's menus in
        // order, each page with whether it is filled; what goes in it is the
        // tile's tooltip.
        $this->assertStringContainsString('School School Details Filled', $text);
        $this->assertStringContainsString('About Us About School Filled History Filled Vision Empty', $text);
        $this->assertStringContainsString('Photogallery Photo Gallery Empty', $text);
        $this->assertStringContainsString('Mandatory Disclosure Mandatory Disclosures Empty', $text);
        $this->assertStringContainsString('The Documents menu fills itself', $text);
        $this->assertStringContainsString('Contact Contact Us Filled', $text);   // the profile's
        $this->assertStringContainsString('Query Form', $text);
        $this->assertStringContainsString('title="Name, logo, phone, address and social links — on every page"', $list->html());
        $this->assertStringContainsString('title="Text · Photo beside the text · More photos"', $list->html());
        $this->assertStringContainsString('title="A form — nothing to fill in"', $list->html());
        $this->assertStringContainsString("{$off} more pages switched off", $text);
        $this->assertStringNotContainsString("Chairman's Desk", $text);

        $list->call('openPage', 'history')->assertSet('open', 'history')
            ->assertSee('Website Data › About Us')
            ->call('closePage')->assertSet('open', '')->assertSee('School Details');
        $list->call('openPage', 'no-such-page')->assertSet('open', '');
    }

    public function test_a_page_shows_its_blocks_and_saves_only_them(): void
    {
        $this->website(['content' => ['motto' => 'Learn to Serve', 'history_text' => 'Our story so far.']]);

        $page = Livewire::test(WebsiteData::class)
            ->call('openPage', 'history')
            ->assertSee(['Heading', 'Text', 'Photo beside the text', 'More photos', 'Upload photo', 'Add photos'])
            ->assertSet('data.heading', 'History')
            ->assertSet('data.body', 'Our story so far.');   // About School's, while it has none of its own

        // The History page on the site shows it too.
        [, $html] = $this->render('history');
        $this->assertStringContainsString('Our story so far.', $html);

        $page->set('data.body', "Founded in 1990.\n\nNow 1,200 students.")->assertSet('dirty', true)->call('save')->assertSet('dirty', false);

        $content = SchoolWebsite::first()->content;
        $this->assertSame("Founded in 1990.\n\nNow 1,200 students.", $content['pages']['history']['body']);
        $this->assertSame('Learn to Serve', $content['motto']);
        foreach (['school_name', 'phone', 'address', 'logo'] as $copied) {
            $this->assertArrayNotHasKey($copied, $content, $copied);
        }

        // A page with every block of its own emptied leaves the site's content.
        $page->set('data.body', '')->call('save');
        $this->assertArrayNotHasKey('history', SchoolWebsite::first()->content['pages'] ?? []);
    }

    public function test_photos_and_files_upload_as_soon_as_they_are_picked(): void
    {
        Storage::fake('s3');
        $this->website();

        $page = Livewire::test(WebsiteData::class)->call('openPage', 'labs')
            ->set('uploads.image', UploadedFile::fake()->image('lab.jpg'))
            ->set('uploads.photos', UploadedFile::fake()->image('a.jpg'))   // several picked: sent one by one
            ->set('uploads.photos', UploadedFile::fake()->image('b.png'))
            ->assertHasNoErrors();
        $this->assertStringContainsString('admin/website/1/', $page->get('data.image'));
        $this->assertCount(2, $page->get('data.photos'));

        $page->set('uploads.image', UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'))
            ->assertHasErrors('uploads.image');
        $this->assertStringContainsString('admin/website/1/', $page->get('data.image'));   // the photo stays

        $page->call('removeRow', 'photos', 0)->call('save');
        $labs = SchoolWebsite::first()->content['pages']['labs'];
        $this->assertSame($page->get('data.image'), $labs['image']);
        $this->assertSame(1, count(explode("\n", $labs['photos'])));
        Storage::disk('s3')->assertExists('admin/website/1/' . basename($labs['image']));

        // Mandatory Disclosures: CBSE's list to upload against, a PDF a row.
        $docs = Livewire::test(WebsiteData::class)->call('openPage', 'disclosures');
        $this->assertCount(count(SchoolWebsite::defaultDisclosures()), $docs->get('data.documents'));
        $docs->set('uploads.documents.0.file', UploadedFile::fake()->create('affiliation.pdf', 200, 'application/pdf'))
            ->assertHasNoErrors()
            ->set('data.documents.2.file', 'https://youtu.be/aqz-KE-bpKQ')
            ->call('save');
        $rows = SchoolWebsite::first()->content['documents']['disclosures'];
        $this->assertStringEndsWith('.pdf', $rows[0]['file']);
        $this->assertSame('https://youtu.be/aqz-KE-bpKQ', $rows[2]['file']);

        // The gallery takes several photos at once, a row each, and keeps their order.
        $gallery = Livewire::test(WebsiteData::class)->call('openPage', 'gallery')
            ->set('uploads.gallery', UploadedFile::fake()->image('1.jpg'))
            ->set('uploads.gallery', UploadedFile::fake()->image('2.jpg'))
            ->set('data.gallery.1.caption', 'Annual Day')
            ->call('moveRow', 'gallery', 1, -1)
            ->call('save');
        $this->assertSame('Annual Day', SchoolWebsite::first()->content['gallery'][0]['caption']);
        $this->assertCount(2, $gallery->get('data.gallery'));
    }

    public function test_the_school_is_put_on_the_map(): void
    {
        SchoolWebsite::flushEventListeners();   // going live tells the super admins; no users table here
        $this->website(['status' => true]);

        $home = Livewire::test(WebsiteData::class)->call('openPage', 'home')
            ->assertSee(['Our Location', 'paste a Google Maps link', 'Use my current location', 'How do I get the link?'])
            ->assertSet('data.location.address', 'Fatehabad Road, Agra')
            ->assertSee('Found from the address');

        // A place link from Google Maps: its own pin, not the view's centre.
        $home->set('data.location.link', 'https://www.google.com/maps/place/Sunrise+Public+School/@27.1702,78.0012,17z/data=!3m1!4b1!4m6!3m5!1s0x0:0x0!8m2!3d27.1767!4d78.0081!16s')
            ->call('setMapLink')
            ->assertHasNoErrors()
            ->assertSet('data.location.lat', '27.1767')
            ->assertSet('data.location.lng', '78.0081')
            ->assertSet('data.location.link', '')
            ->assertSee('27.1767, 78.0081');

        $home->set('data.location.link', 'my school')->call('setMapLink')->assertHasErrors('data.location.link');

        // The phone's own location.
        $home->call('setPin', 27.2, 78.05)->assertSet('data.location.lat', '27.2')->call('save');
        $content = SchoolWebsite::first()->content;
        $this->assertSame(['27.2', '78.05'], [$content['map_lat'], $content['map_lng']]);
        $this->assertArrayNotHasKey('address', $content);   // still the profile's

        [, $html] = $this->render('');
        $this->assertStringContainsString('src="https://maps.google.com/maps?q=27.2,78.05&amp;z=16&amp;output=embed"', $html);
        [, $contact] = $this->render('contact');
        $this->assertStringContainsString('maps.google.com/maps?q=27.2,78.05', $contact);

        // The embed code from Google Maps' Share → Embed a map.
        $home->set('data.location.link', '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12" width="600"></iframe>')
            ->call('setMapLink')
            ->assertSet('data.location.embed', 'https://www.google.com/maps/embed?pb=!1m18!1m12')
            ->assertSet('data.location.lat', '')
            ->call('save');
        [, $html] = $this->render('');
        $this->assertStringContainsString('src="https://www.google.com/maps/embed?pb=!1m18!1m12"', $html);

        // Removed: back to the address.
        $home->call('clearPin')->call('save');
        [, $html] = $this->render('');
        $this->assertStringContainsString('maps.google.com/maps?q=Sunrise+Public+School%2C+Fatehabad+Road%2C+Agra', $html);

        // Playful shows the map on Contact Us.
        SchoolWebsite::first()->update(['template' => 'kider', 'content' => ['map_lat' => '27.2', 'map_lng' => '78.05']]);
        [, $contact] = $this->render('contact');
        $this->assertStringContainsString('maps.google.com/maps?q=27.2,78.05', $contact);
    }

    public function test_google_maps_links_are_read(): void
    {
        $this->assertSame(['lat' => '27.9512', 'lng' => '78.1234'], MapLink::parse(' 27.9512, 78.1234 '));
        $this->assertSame(['lat' => '27.1767', 'lng' => '78.0081'],
            MapLink::parse('https://www.google.co.in/maps/place/X/@27.17,78.00,17z/data=!4m6!3m5!8m2!3d27.1767!4d78.0081'));
        $this->assertSame(['lat' => '27.17', 'lng' => '78'], MapLink::parse('https://www.google.com/maps/@27.17,78.0,15z'));
        $this->assertSame(['lat' => '27.1', 'lng' => '78.2'], MapLink::parse('https://maps.google.com/?q=27.1,78.2'));
        $this->assertSame(['lat' => '27.1', 'lng' => '78.2'], MapLink::parse('https://www.google.com/maps/search/27.1,+78.2'));

        // A phone's share link is followed to where it leads.
        $follow = fn ($url) => $url === 'https://maps.app.goo.gl/Ab12'
            ? 'https://www.google.com/maps/place/Sunrise/@27.1,78.1,17z/data=!3d27.11!4d78.12' : '';
        $this->assertSame(['lat' => '27.11', 'lng' => '78.12'], MapLink::parse('https://maps.app.goo.gl/Ab12', $follow));
        $this->assertNull(MapLink::parse('https://maps.app.goo.gl/Nope', $follow));

        // Only a name: the map searches for it.
        $this->assertSame(['embed' => 'https://maps.google.com/maps?q=Sunrise+Public+School+Agra&z=16&output=embed'],
            MapLink::parse('https://www.google.com/maps/search/Sunrise+Public+School+Agra'));
        $this->assertSame(['embed' => 'https://www.google.com/maps/embed?pb=!1m1'],
            MapLink::parse('<iframe src="https://www.google.com/maps/embed?pb=!1m1"></iframe>'));

        foreach (['', 'my school', '95, 200', '0, 0', 'https://example.com/maps/@27.1,78.1,17z', 'https://maps.app.goo.gl.evil.com/x'] as $bad) {
            $this->assertNull(MapLink::parse($bad, fn () => ''), $bad);
        }
    }

    public function test_school_details_follow_the_template(): void
    {
        $this->website(['template' => 'kider']);

        $details = Livewire::test(WebsiteData::class)->call('openPage', 'details')
            ->assertSee(['School name', 'Tagline', 'Logo', 'Social links'])
            ->assertDontSee("Board's logo")
            ->assertSet('data.school_name', 'Sunrise Public School')
            ->set('data.school_name', '')
            ->call('save')
            ->assertHasErrors('data.school_name');

        SchoolWebsite::first()->update(['template' => 'classic']);
        Livewire::test(WebsiteData::class)->call('openPage', 'details')
            ->assertSee(["Board's logo", 'Page banner photo'])
            ->assertDontSee('Tagline');

        // Playful's home has its banner, highlights and why-us; Classic's its slider and map.
        Livewire::test(WebsiteData::class)->call('openPage', 'home')->assertSee(['Slider', 'Our Location'])->assertDontSee('Highlights');
        SchoolWebsite::first()->update(['template' => 'kider']);
        Livewire::test(WebsiteData::class)->call('openPage', 'home')->assertSee(['Banner title', 'Highlights', 'Why choose us'])->assertDontSee('Our Location');
    }
}

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
            $t->string('template')->default('classic');
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

    /**
     * User's asks of 2026-10-21: each page's header has an Enabled / Disabled
     * switch — on, the page shows on the site and in its menu; off, it is gone
     * from both; Save answers with what its popup says; the header strips
     * stay whole while the page scrolls (data-lms-pin).
     */
    public function test_a_page_is_switched_on_or_off_from_its_header_and_save_says_it_is_saved(): void
    {
        // Created quietly: a site created live tells the super admins.
        SchoolWebsite::withoutEvents(fn () => $this->website(['domain' => 'sunrise.test', 'status' => true, 'pages' => ['home', 'about', 'campus-life'],
            'content' => ['pages' => ['campus-life' => ['heading' => 'Campus', 'body' => 'Green and open.']]]]));

        $wd = Livewire::test(WebsiteData::class)->call('openPage', 'campus-life');
        $this->assertStringContainsString('data-lms-pin', $wd->html());
        $this->assertStringContainsString('aria-checked="true"', $wd->html());
        $wd->assertSee(['Enabled', 'View page'])->assertDontSee('it is not shown on your website or in its menu');

        // Off: gone from the site and its menu; the page says so, and View page goes.
        $wd->call('setPageOn', false)->assertReturned(['title' => 'Campus & School Life is disabled', 'sub' => 'It no longer shows on your website.']);
        $this->assertSame(['home', 'about'], SchoolWebsite::first()->pages);
        $this->assertSame(404, $this->render('campus-life')[0]);
        $this->assertStringNotContainsString('href="http://localhost/campus-life"', $this->render('home')[1]);
        $this->assertStringContainsString('aria-checked="false"', $wd->html());
        $wd->assertSee(['Disabled', 'it is not shown on your website or in its menu'])->assertDontSee('View page');
        $wd->call('back');
        $this->assertStringContainsString('Campus & School Life Disabled', $this->text($wd));

        // On again: back on the site, in the catalogue's order; what was written is kept.
        $wd->call('openPage', 'campus-life')->call('setPageOn', true)
            ->assertReturned(['title' => 'Campus & School Life is enabled', 'sub' => 'It now shows on your website and in its menu.']);
        $this->assertSame(['home', 'about', 'campus-life'], SchoolWebsite::first()->pages);
        $this->assertSame(200, $this->render('campus-life')[0]);
        $this->assertStringContainsString('Green and open.', $this->render('campus-life')[1]);

        // Save answers with the popup's words; a page that is not saved answers nothing.
        $wd->set('data.body', 'Green, open and safe.')->call('save')
            ->assertReturned(['title' => 'This data has been saved', 'sub' => 'Campus & School Life is updated on your website.'])
            ->assertSet('dirty', false);
        $this->assertSame('Green, open and safe.', SchoolWebsite::first()->content['pages']['campus-life']['body']);
        Livewire::test(WebsiteData::class)->call('openPage', 'details')->set('data.school_name', '')->call('save')
            ->assertHasErrors('data.school_name')->assertReturned(null);

        // Home and School Details have no switch.
        Livewire::test(WebsiteData::class)->call('openPage', 'home')->assertDontSee('role="switch"', false)
            ->call('setPageOn', false)->assertReturned(null);
        Livewire::test(WebsiteData::class)->call('openPage', 'details')->assertDontSee('role="switch"', false);
        $this->assertSame(['home', 'about', 'campus-life'], SchoolWebsite::first()->pages);
    }

    /**
     * The site's menus as tiles in Quick Links' look (user's ask of
     * 2026-10-21); a menu's tile opens its pages as tiles, a page's tile opens
     * its data full width, and Back steps out a level at a time.
     */
    public function test_it_opens_on_the_menus_as_tiles_then_a_menus_pages_then_a_page(): void
    {
        $this->website([
            'pages'   => ['home', 'about', 'vision', 'campus-life', 'gallery', 'disclosures', 'contact', 'query'],
            'content' => ['pages' => ['campus-life' => ['heading' => 'Campus', 'body' => 'Green and open.']]],
        ]);
        $off = count(SchoolWebsite::allPages()) - 8;

        $wd   = Livewire::test(WebsiteData::class)->assertSet('open', '')->assertSet('group', '');
        $text = $this->text($wd);

        // School first, then every menu of the site in order, each with how
        // many of its enabled pages are filled in — a menu with none enabled
        // says Disabled; no pages yet.
        $this->assertStringContainsString('School 1/1 filled Home 1/1 filled About Us 2/3 filled Messaging Disabled Features Disabled '
            . 'Photogallery 0/1 filled Online Admission Disabled Curriculum Disabled Mandatory Disclosure 0/1 filled Contact 1/1 filled More Disabled', $text);
        $this->assertStringContainsString("{$off} pages disabled", $text);
        $this->assertStringNotContainsString('Campus & School Life', $text);

        // A menu: its pages as tiles, each Filled / Empty / Form, or Disabled;
        // what goes in it is the tile's tooltip. The other menus are gone.
        $wd->call('openGroup', 'About Us')->assertSet('group', 'About Us')->assertSet('open', '');
        $text = $this->text($wd);
        $this->assertStringContainsString('About Us Website Data › About Us', $text);
        $this->assertStringContainsString('About School Filled Mission Disabled Vision Empty Values & Goals Disabled Campus & School Life Filled', $text);
        $this->assertStringContainsString('title="Text · Photo beside the text · More photos"', $wd->html());
        $this->assertStringNotContainsString('Photogallery', $text);

        // A page opens on its own, full width; Back goes to the menu's pages, then to the menus.
        $wd->call('openPage', 'campus-life')->assertSet('open', 'campus-life')->assertSet('data.body', 'Green and open.')
            ->assertSee(['Website Data › About Us', 'Details', 'Save']);
        $this->assertStringNotContainsString('Vision Empty', $this->text($wd));
        $wd->call('back')->assertSet('open', '')->assertSet('group', 'About Us');
        $wd->call('back')->assertSet('open', '')->assertSet('group', '');

        // A menu of one page opens that page at once, and its Back is to the menus.
        $wd->call('openGroup', 'Mandatory Disclosure')->assertSet('group', 'Mandatory Disclosure')->assertSet('open', 'disclosures');
        $wd->call('back')->assertSet('open', '')->assertSet('group', '');
        $wd->call('openGroup', 'Contact')->assertSet('open', '');
        $this->assertStringContainsString('Contact Us Filled Career Disabled Query Form Appointment Disabled', $this->text($wd));   // Contact Us: the profile's
        $this->assertStringContainsString('title="A form — nothing to fill in"', $wd->html());
        $wd->call('openGroup', 'School')->assertSet('open', 'details')->assertSee('School name');

        $wd->call('openGroup', 'No such menu')->assertSet('group', 'School');
        $wd->call('openPage', 'no-such-page')->assertSet('open', '');

        // Opened from a link to a page, its menu is picked with it; Vision is the School Profile's.
        Livewire::withQueryParams(['edit' => 'vision'])->test(WebsiteData::class)
            ->assertSet('group', 'About Us')->assertSet('open', 'vision')
            ->assertSee(['From your School Profile', 'Open School Profile']);
    }

    public function test_a_page_shows_its_blocks_and_saves_only_them(): void
    {
        $this->website(['content' => ['motto' => 'Learn to Serve']]);

        $page = Livewire::test(WebsiteData::class)
            ->call('openPage', 'campus-life')
            ->assertSee(['Heading', 'Text', 'Photo beside the text', 'More photos', 'Upload photo', 'Add photos'])
            ->assertSet('data.heading', 'Campus & School Life')
            ->assertSet('data.body', '');

        $page->set('data.body', "Founded in 1990.\n\nNow 1,200 students.")->assertSet('dirty', true)->call('save')->assertSet('dirty', false);

        // The page on the site shows it.
        [, $html] = $this->render('campus-life');
        $this->assertStringContainsString('Now 1,200 students.', $html);

        $content = SchoolWebsite::first()->content;
        $this->assertSame("Founded in 1990.\n\nNow 1,200 students.", $content['pages']['campus-life']['body']);
        $this->assertSame('Learn to Serve', $content['motto']);
        foreach (['school_name', 'phone', 'address', 'logo'] as $copied) {
            $this->assertArrayNotHasKey($copied, $content, $copied);
        }

        // A page with every block of its own emptied leaves the site's content.
        $page->set('data.body', '')->call('save');
        $this->assertArrayNotHasKey('campus-life', SchoolWebsite::first()->content['pages'] ?? []);
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

        // Contact Us shows the map too.
        SchoolWebsite::first()->update(['content' => ['map_lat' => '27.2', 'map_lng' => '78.05']]);
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

    public function test_school_details_are_classics(): void
    {
        // A site still naming Playful (kider) is edited as Classic — the only template.
        $this->website(['template' => 'kider']);

        Livewire::test(WebsiteData::class)->call('openPage', 'details')
            ->assertSee(['School name', 'Logo', 'Social links', "Board's logo", 'Page banner photo'])
            ->assertDontSee('Tagline')
            ->assertSet('data.school_name', 'Sunrise Public School')
            ->set('data.school_name', '')
            ->call('save')
            ->assertHasErrors('data.school_name');

        // Home has Classic's slider and map, none of Playful's banner, highlights or why-us.
        Livewire::test(WebsiteData::class)->call('openPage', 'home')
            ->assertSee(['Slider', 'Our Location'])
            ->assertDontSee(['Banner title', 'Highlights', 'Why choose us']);
    }
}

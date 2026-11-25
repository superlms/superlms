<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\KeepStateOnReload;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A refresh keeps a panel page where it was (user's ask of 2026-11-25;
 * App\Support\KeepStateOnReload): the tab, the filters, the panel opened —
 * with lists its hooks load read afresh — while opening the page any other
 * way starts it fresh, and nothing is kept for a guest or for a password.
 */
class KeepStateOnReloadTest extends TestCase
{
    private const SESSION_ID = 'kkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkkk';

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.key' => 'base64:' . base64_encode(str_repeat('s', 32))]);

        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('role')->nullable();
            $t->unsignedBigInteger('organization_id')->nullable(); $t->timestamps();
        });

        KeepStateFixture::$db = [
            'standards' => ['Class 1', 'Class 2'],
            'students'  => ['5' => ['Asha', 'Ravi']],
            'names'     => [7 => 'Navya'],
        ];
        KeepStateFixture::$hookRuns = 0;

        // One browser: every request carries the same session cookie.
        $this->withCookie(config('session.cookie'), self::SESSION_ID)->withCredentials();

        Livewire::component('keep-state-fixture', KeepStateFixture::class);
        Route::middleware('web')->get('/__keep-state', fn () => Blade::render(
            '<html><body>@livewire(\'keep-state-fixture\', [], key(\'page\'))</body></html>'));
    }

    private function signIn(): void
    {
        $this->actingAs(User::forceCreate(['name' => 'Head', 'role' => 'admin', 'organization_id' => 9]), 'admin');
    }

    /** What is kept for the page in this browser's session. */
    private function kept(): ?array
    {
        session()->setId(self::SESSION_ID);

        return KeepStateOnReload::kept('/__keep-state', 'keep-state-fixture', 'page');
    }

    private function open(array $headers = []): string
    {
        return $this->get('/__keep-state', $headers)->assertOk()->getContent();
    }

    private function snapshotOf(string $html): string
    {
        $this->assertMatchesRegularExpression('/wire:snapshot="([^"]*)"/', $html);
        preg_match('/wire:snapshot="([^"]*)"/', $html, $m);

        return html_entity_decode($m[1], ENT_QUOTES);
    }

    /** One Livewire request, as the browser sends it; returns the new snapshot. */
    private function update(string $snapshot, array $updates, array $calls = []): string
    {
        return $this->postJson('/livewire/update',
            ['components' => [['snapshot' => $snapshot, 'updates' => $updates, 'calls' => $calls]]],
            ['X-Livewire' => '1'],
        )->assertOk()->json('components.0.snapshot');
    }

    /** Pick a tab and a class (its hook loads the students), type a password; then a section and open a panel. */
    private function useThePage(): void
    {
        $snap = $this->snapshotOf($this->open());
        $snap = $this->update($snap, ['tab' => 'fees', 'classId' => '5', 'password' => 'secret1']);
        $this->update($snap, ['sectionId' => 'B'], [['path' => '', 'method' => 'openPanel', 'params' => [7]]]);
    }

    public function test_a_reload_brings_the_page_back_where_it_was_with_fresh_lists(): void
    {
        $this->signIn();
        $this->useThePage();

        // Meanwhile the database changes.
        KeepStateFixture::$db['students']['5'] = ['Asha', 'Ravi', 'Kiran'];
        KeepStateFixture::$db['standards']     = ['Class 1', 'Class 2', 'Class 3'];
        KeepStateFixture::$hookRuns = 0;

        $html = $this->open(['Cache-Control' => 'max-age=0']);

        $this->assertStringContainsString('tab:fees|', $html);                 // the tab, though mount() sets 'home'
        $this->assertStringContainsString('class:5|', $html);
        $this->assertStringContainsString('section:B|', $html);                // kept though the class's hook clears it
        $this->assertStringContainsString('students:Asha,Ravi,Kiran|', $html);  // the hook ran: the list is fresh
        $this->assertSame(1, KeepStateFixture::$hookRuns);
        $this->assertStringContainsString('panel:open:Navya|', $html);         // the panel a method opened
        $this->assertStringContainsString('std:Class 1,Class 2,Class 3|', $html); // mount()'s own list stays fresh
        $this->assertStringContainsString('pw:|', $html);                      // a password is never kept
        $kept = $this->kept();
        $this->assertSame('fees', $kept['tab'] ?? null);
        $this->assertArrayNotHasKey('password', $kept);

        // Ctrl+Shift+R (no-cache) does the same.
        $this->assertStringContainsString('panel:open:Navya|', $this->open(['Cache-Control' => 'no-cache', 'Pragma' => 'no-cache']));
    }

    public function test_opening_the_page_any_other_way_starts_it_fresh_and_forgets_it(): void
    {
        $this->signIn();
        $this->useThePage();

        $html = $this->open();   // a link, the sidebar, a typed address
        $this->assertStringContainsString('tab:home|', $html);
        $this->assertStringContainsString('panel:closed:|', $html);

        // …and a reload after that is the fresh page too.
        $this->assertNull($this->kept());
        $this->assertStringContainsString('tab:home|', $this->open(['Cache-Control' => 'max-age=0']));
    }

    public function test_wire_navigate_starts_fresh(): void
    {
        $this->signIn();
        $this->useThePage();

        $html = $this->open(['Cache-Control' => 'max-age=0', 'X-Livewire-Navigate' => '1']);
        $this->assertStringContainsString('tab:home|', $html);
        $this->assertStringContainsString('panel:closed:|', $html);
    }

    public function test_nothing_is_kept_for_a_guest(): void
    {
        $this->useThePage();

        $this->assertNull($this->kept());
        $this->assertStringContainsString('tab:home|', $this->open(['Cache-Control' => 'max-age=0']));
    }
}

class KeepStateFixture extends Component
{
    public static array $db = [];
    public static int $hookRuns = 0;

    public string $tab = 'home';
    public $classId = '';
    public $sectionId = '';
    public array $students = [];
    public bool $showPanel = false;
    public $panelId = null;
    public array $panelInfo = [];
    public string $password = '';
    public array $standards = [];

    public function mount(): void
    {
        $this->tab = 'home';
        $this->standards = static::$db['standards'];
    }

    public function updatedClassId(): void
    {
        static::$hookRuns++;
        $this->sectionId = '';
        $this->students = static::$db['students'][$this->classId] ?? [];
    }

    public function openPanel($id): void
    {
        $this->showPanel = true;
        $this->panelId = $id;
        $this->panelInfo = ['id' => $id, 'name' => static::$db['names'][$id] ?? '?'];
    }

    public function render()
    {
        return <<<'blade'
            <div>tab:{{ $tab }}| class:{{ $classId }}| section:{{ $sectionId }}| students:{{ implode(',', $students) }}| panel:{{ $showPanel ? 'open' : 'closed' }}:{{ $panelInfo['name'] ?? '' }}| pw:{{ $password }}| std:{{ implode(',', $standards) }}|</div>
        blade;
    }
}
